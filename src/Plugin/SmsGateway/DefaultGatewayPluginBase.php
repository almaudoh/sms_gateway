<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway;

use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Plugin\SmsGatewayPluginBase;

/**
 * Provides a default implementation of the Gateway interface with additional
 * helper functions.
 *
 * Most gateways should subclass this for easier implementations.
 */
abstract class DefaultGatewayPluginBase extends SmsGatewayPluginBase {

  /**
   * The random number generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected static $random;

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms, array $options) {
    // Wrapper method for sending messages.
    // Provides basic cleanup functionality prior to passing onto gateway send
    // command. Subclasses will implement additional logic in the doSend() method
    // and also call the doCommand('send') method to dispatch messages.

    // Call subclass implementation to process sending.
    return new SmsMessageResult($this->doSend($sms, $options));
  }

  /**
   * {@inheritdoc}
   */
  public function balance() {
    try {
      $result = $this->doCommand('credits', []);
    }
    catch (\Exception $e) {
      // Return helpful information.
      $result['credit_balance'] = 'Not currently available';
    }
    return isset($result['credit_balance']) ? $result['credit_balance'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function pullDeliveryReports(array $message_ids = NULL) {
    return $this->doCommand('report', ['message_ids' => $message_ids]);
  }

  /**
   * Tests the gateway.
   *
   * @param array $config
   *    Optional configuration parameters to test gateway with.
   *
   * @return bool
   *   TRUE if gateway is available, FALSE otherwise.
   */
  public function test(array $config = NULL) {
    $result = $this->doCommand('test', [], $config);
    return $result['status'];
  }

  /**
   * Handles the low-level connection for sending messages.
   *
   * This method implicitly handles the splitting of messages into smaller
   * batches to avoid exceeding HTTP-GET and HTTP-POST data size limits which
   * may cause timeouts, truncated messages, and other sorts of weird behavior
   * depending on the web server.
   *
   * Notes: 'status' of TRUE means successful communication with the SMS server
   * and success in message request. Failure of individual messages will be
   * captured in the message report status for that number.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS message to be sent.
   * @param array $options
   *   Options to be applied while processing this SMS.
   *
   * @return array
   *   The combined message result array. This array is converted into an 
   *   SmsMessageResult object and must have the following keys:
   *     - status: true or false - whether the message sending was successful.
   *     - error_message: the error message to display if status above is false.
   *     - credit_used: the amount of SMS credits used in this transaction.
   *     - credit_balance: the amount of SMS credits left.
   *     - reports: an array of DeliveryReportInterface objects keyed by the
   *       phonenumber.
   *
   * @see \Drupal\sms\Message\SmsDeliveryReportInterface
   */
  protected function doSend(SmsMessageInterface $sms, array $options) {
    // Initialize the composite results array.
    $results = array(
      'status' => FALSE,
      'error_message' => '',
      'credit_used' => 0,
      'credit_balance' => 0,
      'report' => array(),
    );
    // Batch the recipients according to the limits of the SMS gateway.
    $recipients = $sms->getRecipients();
    $max_size = $this->maxRecipientCount();
    if (!isset($max_size)) {
      // If not set, allow all messages through, use very high limit.
      $max_size = 1000000;
    }
    // Send the message in batches and accumulate the results.
    while (count($recipients) > 0) {
      $batch = array_slice($recipients, 0, $max_size);
      $recipients = array_slice($recipients, $max_size);
      if (!empty($batch)) {
        $batch_result = $this->doCommand('send', [
          'recipients' => $batch,
          'message' => $sms->getMessage(),
          'sender' => $sms->getSender(),
        ], $options);
        // Combine current batch results with cumulative results.
        @$results['status'] = $results['status'] || $batch_result['status'];
        @$results['error_message'] .= "\n" . $batch_result['message'];
        @$results['credit_used'] += $batch_result['credit_used'];
        @$results['credit_balance'] = $batch_result['credit_balance'];
        @$results['report'] += (array)$batch_result['report'];
      }
    }
    return $results;
  }

  /**
   * Handles the low-level communications with the SMS gateway server.
   *
   * @param string $command
   *   The command to be executed. The implementation determines exact commands,
   *   but it is normally either of:
   *   - send: to send and SMS message
   *   - test: to test the gateway connections
   *   - credits: to get the current credits balance
   *   - report: to pull delivery reports
   * @param array $data
   *   An array containing the data needed to execute the command. Information
   *   that can be in this array is as follows:
   *   - recipients: An array of recipient numbers.
   *   - message: The SMS message to be dispatched.
   *   - sender: The sender ID of the message to be sent.
   *   - message_ids: An array of message IDs to poll for delivery reports.
   *   - options: Additional options for the SMS gateway.
   * @param array $config
   *   (optional) The configuration to be used for sending the message. If not
   *   provided, the stored configuration will be used. This option is for cases
   *   where a particular configuration is to be tested.
   *
   * @return array
   *   A structured array describing the results of the SMS command.
   *
   * @see \Drupal\sms\Plugin\SmsGatewayPluginInterface::send() for the structure
   *   of the return value.
   */
  abstract protected function doCommand($command, array $data, array $config = NULL);

  /**
   * Provides the web resource or path that corresponds to a command.
   *
   * @param string $command
   *   The command for which a web resource or path is needed.
   *
   * @return string
   */
  abstract protected function getResourceForCommand($command);

  /**
   * Builds the request URL based on the settings configured for this gateway.
   *
   * @param string $command
   *   The command for which the URL is being built.
   *
   * @return string
   *   The fully qualified URI for executing the specified command on the SMS
   *   server.
   */
  protected function buildRequestUrl($command, $config) {
    if ($config['ssl']) {
      $scheme = 'https';
    }
    else {
      $scheme = 'http';
    }
    $server = trim($config['server'], '/\\') . (isset($config['port']) ? ':' . $config['port'] : '');
    return $scheme . '://' . $server .  $this->getResourceForCommand($command);
  }

  /**
   * Encapsulates the http client request.
   *
   * @param string $url
   *   The URL of the SMS gateway server to make the request.
   * @param array $body
   *   (optional) An array of key-value pairs used to build up the query string.
   *   Defaults to an empty array.
   * @param string $method
   *   (optional) The HTTP method to use for the request. Defaults to 'GET'.
   * @param array $headers
   *   (optional) An array of HTTP headers to send along with the request.
   *   Defaults to empty array.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *
   * @throws \GuzzleHttp\Exception\ClientException
   *   If the request fails (404 - unknown resource, or other custom exception).
   */
  protected function httpRequest($url, $query = [], $method = 'GET', $headers = [], $body) {
    $client = $this->httpClient();
    return $client->request($method, $url, ['headers' => $headers, 'body' => $body, 'query' => $query]);
  }

  /**
   * Drupal HTTP client service wrapper for unit testing.
   *
   * @return \GuzzleHttp\Client
   */
  protected function httpClient() {
    return \Drupal::httpClient();
  }

  /**
   * Cleans up the message and removes non-compatible characters.
   *
   * @param string $message
   *   The message to be cleaned up.
   *
   * @return string
   *   The cleaned up message.
   */
  protected function cleanMessage($message) {
    $search = str_split('ëí`ìî');
    $replace = str_split('\'\'\'""');
    $message = str_replace($search, $replace, $message);
    $message = urlencode($message);
    return $message;
  }

  /**
   * Cleans up sender id and removes non-compatible characters.
   *
   * @param string $sender
   *   The sender id to be cleaned up.
   *
   * @return string
   *   The cleaned up sender.
   */
  protected function cleanSender($sender) {
    return str_replace(' ', '', urlencode(substr($sender, 0, 12)));
  }

  /**
   * Gets the max. allowed number of recipients in a request for this gateway.
   *
   * @return int
   */
  protected function maxRecipientCount() {
    return 400;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ssl' => TRUE,
      'server' => '',
      'port' => 80,
      'username' => '',
      'password' => '',
      'method' => 'GET',
      'reports' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $t_args = array(
      '@name' => $this->gatewayName,
    );

    $form['balance'] = array(
      '#type' => 'item',
      '#title' => $this->t('Current balance'),
      '#markup' => $this->balance(),
    );
    $form['ssl'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSL Encyption'),
      '#description' => $this->t('Ensure you have SSL properly configured on your server.'),
      '#default_value' => $config['ssl'],
    );
    $form['server'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Server URL'),
      '#description' => $this->t('The url for accessing the @name api server.', $t_args),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $config['server'],
    );
    $form['port'] = array(
      '#type' => 'number',
      '#title' => $this->t('API Server port number'),
      '#description' => $this->t('The port number for accessing the @name api server.', $t_args),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $config['port'],
    );
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The username on the @name account.', $t_args),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $config['username'],
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('The current password on the @name account.', $t_args),
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $config['password'],
    );
    $form['reports'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Activate push delivery reports'),
      '#description' => $this->t('Delivery report path: @path', ['@path' => $this->getDeliveryReportPath()]),
      '#default_value' => $config['reports'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$this->test($form_state->getValues())) {
      $error = $this->getError();
      $form_state->setErrorByName($error['form_element'], $this->t('The settings could not be validated. A gateway error occurred: @error.',
        array('@error' => $error['description'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the gateway's submission handling only if no errors occurred.
    if (!$form_state->getErrors()) {
      $this->configuration['ssl'] = $form_state->getValue('ssl');
      $this->configuration['server'] = $form_state->getValue('server');
      $this->configuration['port'] = $form_state->getValue('port');
      $this->configuration['username'] = $form_state->getValue('username');
      $this->configuration['password'] = $form_state->getValue('password');
      $this->configuration['reports'] = $form_state->getValue('reports');
    }
  }

  /**
   * Generates random message IDs for gateways that don't generate IDs.
   */
  protected function randomMessageID() {
    return time() . $this->getRandomGenerator()->name(4) . '-' . $this->getRandomGenerator()->name(8);
  }

  /**
   * Returns the random generator.
   *
   * @return \Drupal\Component\Utility\Random
   */
  protected static function getRandomGenerator() {
    if (!isset(self::$random)) {
      self::$random = new Random();
    }
    return self::$random;
  }

}
