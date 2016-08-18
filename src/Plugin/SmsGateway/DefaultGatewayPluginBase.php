<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway;

use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

abstract class DefaultGatewayPluginBase extends SmsGatewayPluginBase implements TestableGatewayPluginInterface {

  /**
   * The random number generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected static $random;

  /**
   * The errors that have been generated by this gateway plugin instance.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
    // Wrapper method for sending messages.
    // Provides basic cleanup functionality prior to passing onto gateway send
    // command. Subclasses will implement additional logic in the doSend() method
    // and also call the doCommand('send') method to dispatch messages.

    // Call subclass implementation to process sending.
    return new SmsMessageResult($this->doSend($sms));
  }

  /**
   * {@inheritdoc}
   */
  public function balance() {
    $result = $this->doCommand('credits', []);
    if (!$result['status']) {
      // Request failed for some reason, so return helpful information.
      $result['credit_balance'] = 'Not currently available';
    }
    return isset($result['credit_balance']) ? $result['credit_balance'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeliveryReports(array $message_ids = NULL) {
    return $this->doCommand('report', ['message_ids' => $message_ids]);
  }

  /**
   * {@inheritdoc}
   */
  public function test(array $config = NULL) {
    $result = $this->doCommand('test', [], $config);
    return $result;
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
   * captured in the message report status for that recipient's number.
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
  protected function doSend(SmsMessageInterface $sms) {
    // Initialize the composite results array.
    $default_result = [
      'status' => FALSE,
      'error_message' => '',
      'credit_used' => 0,
      'credit_balance' => 0,
      'reports' => [],
    ];
    $results = $default_result;
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
          'sender' => $sms->getSenderNumber(),
          'options' => $sms->getOptions(),
        ]) + $default_result;
        // Combine current batch results with cumulative results.
        $results['status'] = $results['status'] || $batch_result['status'];
        $results['error_message'] .= "\n" . $batch_result['error_message'];
        $results['credit_used'] += $batch_result['credit_used'];
        $results['credit_balance'] = $batch_result['credit_balance'];
        $results['reports'] += (array)$batch_result['reports'];
      }
    }
    // Remove extra new-lines from the message.
    $results['error_message'] = trim($results['error_message']);
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
   * @return array|null
   *   A structured array describing the results of the SMS command.
   *
   * @see \Drupal\sms\Message\SmsMessageResultInterface for the structure of the
   *   return value.
   */
  protected function doCommand($command, array $data, array $config = NULL) {
    // Get params needed to make the HTTP request from the sub-class.
    try {
      $config = isset($config) ? $config : $this->getConfiguration();
      $params = $this->getHttpParametersForCommand($command, $data, $config) + [
          'query' => [],
          'method' => 'GET',
          'headers' => [],
          'body' => '',
        ];
    }
    catch (InvalidCommandException $e) {
      return array(
        'status' => FALSE,
        'error_message' => $this->t('Invalid command (@command) for gateway @gateway', [
          '@command' => $command,
          '@gateway' => $this->getPluginId(),
        ]),
      );
    }

    // Catch Guzzle Exceptions and show the user a useful message.
    try {
      $url = $this->buildRequestUrl($command, $config);
      return $this->handleResponse($this->httpRequest($url, $params['query'], $params['method'], $params['headers'], $params['body']), $command, $data);
    }
    catch (ConnectException $e) {
      return [
        'status' => FALSE,
        'error_message' => $this->t('Could not connect to the gateway server (@code) @message', [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage()
        ]),
      ];
    }
    catch (GuzzleException $e) {
      return [
        'status' => FALSE,
        'error_message' => $this->t('HTTP response exception (@code) @message', [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage()
        ]),
      ];
    }
    catch (\Exception $e) {
      return [
        'status' => FALSE,
        'error_message' => $this->t('HTTP request exception (@code) @message', [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage()
        ]),
      ];
    }
  }

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
   * Calculates the HTTP parameters needed to execute the command.
   *
   * @param string $command
   *   The command for which the HTTP parameters are to be calculated.
   * @param array $data
   *   An array containing the data needed to execute the command.
   * @param array|NULL $config
   *   The configuration to be used for sending the message.
   *
   * @return array
   *   An array containing the data needed to make a request:
   *   - query: the query string to be put on the request.
   *   - method: the HTTP request method.
   *   - headers: an array containing HTTP header names and values.
   *   - body: the string to be inserted into the HTTP body.
   *
   * @see \Drupal\sms_gateway\Plugin\SmsGateway\DefaultGatewayPluginBase::doCommand().
   */
  abstract protected function getHttpParametersForCommand($command, array $data, array $config);

  /**
   * Handles the response from the SMS gateway and returns a structured format.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response to be handled.
   * @param string $command
   *   The command.
   * @param array $data
   *   Additional data used by the command.
   *
   * @return array
   *   Structured key-value array containing the processed result depending on
   *   the command.
   */
  abstract protected function handleResponse(ResponseInterface $response, $command, $data);

  /**
   * Builds the request URL based on the settings configured for this gateway.
   *
   * @param string $command
   *   The command for which the URL is being built.
   * @param array $config
   *   An array containing the configuration to be used for building the URL.
   *
   * @return string
   *   The fully qualified URI for executing the specified command on the SMS
   *   server.
   */
  protected function buildRequestUrl($command, array $config) {
    if ($config['ssl']) {
      $scheme = 'https';
    }
    else {
      $scheme = 'http';
    }
    $server = trim($config['server'], '/\\') . (!empty($config['port']) ? ':' . $config['port'] : '');
    return $scheme . '://' . $server .  $this->getResourceForCommand($command);
  }

  /**
   * Encapsulates the http client request.
   *
   * @param string $url
   *   The URL of the SMS gateway server to make the request.
   * @param array $query
   *   (optional) An array of key-value pairs used to build up the query string.
   *   Defaults to an empty array.
   * @param string $method
   *   (optional) The HTTP method to use for the request. Defaults to 'GET'.
   * @param array $headers
   *   (optional) An array of HTTP headers to send along with the request.
   *   Defaults to empty array.
   * @param string $body
   *   (optional) A string to form the body of the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the request fails (404 - unknown resource, or other custom exception).
   */
  protected function httpRequest($url, $query = [], $method = 'GET', $headers = [], $body = '') {
    $client = $this->httpClient();
    return $client->request($method, $url, [
      'headers' => $headers,
      'body' => $body,
      'query' => $query
    ]);
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
    return str_replace(' ', '', urlencode(substr($sender, 0, 11)));
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
      'no_validate' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $t_args = [
      '@name' => $this->pluginDefinition['label'],
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Gateway settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['settings']['reports'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate push delivery reports'),
      '#default_value' => $config['reports'],
    ];
    $form['settings']['balance'] = [
      '#theme_wrappers' => ['form_element'],
      '#title' => $this->t('Current balance'),
      '#markup' => $this->balance(),
    ];
    $form['settings']['ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSL Encryption'),
      '#description' => $this->t('Ensure you have SSL properly configured on your server.'),
      '#default_value' => $config['ssl'],
    ];
    $form['settings']['server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Server URL'),
      '#description' => $this->t('The url for accessing the @name api server.', $t_args),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $config['server'],
      '#required' => TRUE,
    ];
    $form['settings']['port'] = [
      '#type' => 'number',
      '#title' => $this->t('API Server port number'),
      '#description' => $this->t('The port number for accessing the @name api server.', $t_args),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $config['port'],
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['settings']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The username on the @name account.', $t_args),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $config['username'],
      '#required' => TRUE,
    ];
    $form['settings']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('The current password on the @name account.', $t_args),
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $config['password'],
      '#required' => TRUE,
    ];
    $form['settings']['no_validate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not validate gateway settings'),
      '#default_value' => $config['no_validate'],
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $response = $this->test($form_state->getValue('settings'));
    if (!$response['status']) {
      $t_args = [
        '@error' => $response['error_message'],
        '@gateway' => $this->pluginDefinition['label'],
      ];
      if ($form_state->getValue('no_validate')) {
        drupal_set_message($this->t('The @gateway gateway returned an error - @error.', $t_args), 'warning');
      }
      else {
        $form_state->setErrorByName('', $this->t('The settings could not be validated. The @gateway gateway returned an error - @error.',
          $t_args));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the gateway's submission handling only if no errors occurred.
    if (!$form_state->getErrors()) {
      $this->configuration = $form_state->getValue('settings');
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
