<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms_gateway\Plugin\SmsGateway\RouteSms\DeliveryReportHandler;
use Drupal\sms_gateway\Plugin\SmsGateway\RouteSMS\MessageResponseHandler;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds support for sending SMS messages using the RouteSMS gateway.
 *
 * @SmsGateway(
 *   id = "routesms",
 *   label = @Translation("RouteSMS Gateway"),
 * )
 */
class RouteSmsGateway extends DefaultGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function balance() {
    // RouteSMS does not yet implement credit balance.
    return t('Not available.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doCommand($command, array $data, array $config = NULL) {
    $method = 'GET';
    $body = '';
    $query = [];
    $headers = [];
    $config = isset($config) ? $config : $this->getConfiguration();
    if ($command === 'send') {
      // Default values for send command.
      $data += array(
        'isflash' => 0,
        'sender' => \Drupal::config('system.site')->get('name'),
        'numbers' => [],
        'message' => '',
      );
    }
    // Setup username and password.
    $query['username'] = $config['username'];
    $query['password'] = $config['password'];
    $query['type'] = empty($data['isflash']) ? 0 : 1;
    // Delivery report push url. The standard format of the API is as below.
    // http://ip/app/status?unique_id=%7&reason=%2&to=%p&from=%P&time=%t&status=%d
    // http://<hostname>:Port/example.php?sender=%P&mobile=%p&dtSent=%t&msgid=%I&status=%d
    if ($command == 'send' && $config['reports'] && isset($data['options']['delivery_report_url']) && $dlr_url = $data['options']['delivery_report_url']) {
      $query['dlr'] = '1';
      // @todo Do we need the additional query args. It's not in their API doc.
      $query['dlr-url'] = urlencode($dlr_url . '?sender=%P&recipient=%p&time_delivered%t&message_id=%I&status=%d&uuid=%7&reason=%2');
    }

    $default_sender = \Drupal::config('system.site')->get('name');
    $sender = $this->cleanSender(isset($data['sender']) ? $data['sender'] : $default_sender);
    switch ($command) {
      case 'send':
        $query['destination'] = implode(',', $data['recipients']);
        $query['source'] = $sender;
        $query['message'] = $data['message'];
        break;

      case 'test':
        $query['dlr'] = 0;
        $query['destination'] = $config['test_number'];
        $query['source'] = $sender;
        $query['message'] = 'Configuration+Successful';
        break;

      default:
        return array(
          'status' => FALSE,
          'error_message' => $this->t('An error has occurred: Invalid command for gateway'),
        );
    }
    $url = $this->buildRequestUrl($command, $config);

    try {
      return $this->handleResponse($this->httpRequest($url, $query, $method, $headers, $body), $command, $data);
    }
    catch (GuzzleException $e) {
      // This error should not get to the user.
      $t_args = ['@code' => $e->getCode(), '@message' => $e->getMessage()];
      $this->logger()->error('An error occurred during the HTTP request: (@code) @message', $t_args);
      return [
        'status' => FALSE,
        'error_message' => $this->t('An error occurred during the HTTP request: (@code) @message', $t_args),
      ];
    }
  }

  /**
   * Handles the response from the SMS gateway.
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
  protected function handleResponse(ResponseInterface $response, $command, $data) {
    // Check for HTTP errors.
    if ($response->getStatusCode() !== 200) {
      $this->errors[] = [
        'code' => $response->getStatusCode(),
        'message' => $response->getReasonPhrase(),
      ];
      return [
        'status' => FALSE,
        'error_message' => $this->t('An error occurred during the HTTP request: @error',
          ['@error' => $response->getReasonPhrase()]),
      ];
    }

    // Call the message response handler to handle the message response.
    $result = [];
    if ($body = (string) $response->getBody()) {
      $handler = new MessageResponseHandler();
      $result = $handler->handle($body);
    }

    return $result;
  }

  /**
   * Provides the web resource or path that corresponds to a command.
   *
   * @param string $command
   *   The command for which a web resource or path is needed.
   *
   * @return string
   */
  protected function getResourceForCommand($command) {
    switch ($command) {
      case 'send':
      case 'test':
        return '/bulksms/bulksms';

      default:
        return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $handler = new DeliveryReportHandler();
    return $handler->parseDeliveryReport($request->request->all());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'test_number' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['test_number'] = array(
      '#type' => 'number',
      '#title' => t('Test Number'),
      '#description' => t('A number to confirm configuration settings. You will receive an sms if the settings are ok.'),
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $this->configuration['test_number'],
      '#min' => 1,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Process the gateway's submission handling only if no errors occurred.
    if (!$form_state->getErrors()) {
      $this->configuration['test_number'] = $form_state->getValue('test_number');
    }
  }

  /**
   * Converts a string to UCS-2 encoding if necessary.
   *
   * @param string $message
   *   The message string to be converted.
   *
   * @return string|false
   *   Returns the encoded string, or false if the convert function is not
   *   available.
   */
  protected function convertToUnicode($message) {
    $hex1 = '';
    if (function_exists('iconv')) {
      $latin = @iconv('UTF-8', 'ISO-8859-1', $message);
      if (strcmp($latin, $message)) {
        $arr = unpack('H*hex', @iconv('UTF-8', 'UCS-2BE', $message));
        $hex1 = strtoupper($arr['hex']);
      }
      if ($hex1 == '') {
        $hex2 = '';
        $hex = '';
        for ($i = 0; $i < strlen($message); $i++) {
          $hex = dechex(ord($message[$i]));
          $len = strlen($hex);
          $add = 4 - $len;
          if ($len < 4) {
            for ($j = 0; $j < $add; $j++) {
              $hex = "0" . $hex;
            }
          }
          $hex2 .= $hex;
        }
        return $hex2;
      }
      else {
        return $hex1;
      }
    }
    else {
      return FALSE;
    }
  }

}
