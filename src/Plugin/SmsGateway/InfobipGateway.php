<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway;

use Drupal\Component\Serialization\Json;
use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\CreditsResponseHandler;
use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\DeliveryReportHandler;
use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\MessageResponseHandler;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds support for sending SMS messages using the Infobip gateway.
 *
 * @SmsGateway(
 *   id = "infobip",
 *   label = @Translation("Infobip Gateway"),
 * )
 */
class InfobipGateway extends DefaultGatewayPluginBase {

  // Infobip server API endpoints / resources.
  const ENDPOINT_SEND_ADVANCED   = '/sms/1/text/advanced';
  const ENDPOINT_DELIVERY_REPORT = '/sms/1/reports';
  const ENDPOINT_CREDIT_BALANCE  = '/account/1/balance';

  /**
   * {@inheritdoc}
   */
  protected function doCommand($command, array $data, array $config = NULL) {
    $method = 'GET';
    $body = '';
    $query = [];
    $config = isset($config) ? $config : $this->getConfiguration();
    // Set up common headers for the REST request.
    $headers = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
      'Authorization' => 'Basic ' . base64_encode("{$config['username']}:{$config['password']}")
    ];
    if ($command === 'send') {
      // Default values for send command.
      $data += array(
        'isflash' => 0,
        'sender' => \Drupal::config('system.site')->get('name'),
        'numbers' => [],
        'message' => '',
      );
    }

    switch ($command) {
      case 'send':
        // Method is POST for send requests.
        $method = 'POST';
        // Turn the recipient array to the format understood by Infobip.
        $message['destinations'] = array_map(function($recipient) {
          return ['to' => $recipient];
        }, $data['recipients']);
        $message['from'] = $data['sender'];
        $message['text'] = $data['message'];
        $message['flash'] = (bool) $data['isflash'];
        // Configure push delivery reports if URL is specified.
        if ($this->configuration['reports']) {
          $message['notifyUrl'] = $data['options']['delivery_report_url'];
          $message['notifyContentType'] = 'application/json';
        }
        // Set the body to JSON encoded data.
        $body = Json::encode([
          'bulkId' => $this->randomMessageID(),
          'messages' => [$message],
        ]);
        break;

      case 'report':
        // Method is GET for delivery reports pulling.
        $method = 'GET';
        if (isset($data['message_ids'])) {
          if (is_array($data['message_ids'])) {
            $data['message_ids'] = implode(',', $data['message_ids']);
          }
          $query['messageId'] = $data['message_ids'];
        }
        if (isset($data['bulkId'])) {
          $query['bulkId'] = $data['bulkId'];
        }
        break;

      case 'credits':
      case 'test':
      default:
        // Really nothing to do here.
        break;
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
        'error_message' => $this->t('An error occurred during the HTTP request: (@code) @message',
          ['@code' => $response->getStatusCode(), '@message' => $response->getReasonPhrase()]),
      ];
    }
    else {
      if ($command == 'test') {
        // No need for further processing if it was just a gateway test.
        return ['status' => TRUE];
      }
    }

    // Check for Infobip errors. Because Infobip responses (including error
    // codes) are different for each endpoint (i.e. API resource) called, we have
    // to implement different response handlers for each endpoint.
    $result = [];
    if ($body = (string) $response->getBody()) {
      switch ($this->getResourceForCommand($command)) {
        case self::ENDPOINT_SEND_ADVANCED:
          $handler = new MessageResponseHandler();
          $result = $handler->handle($body);
          break;

        case self::ENDPOINT_CREDIT_BALANCE:
          $handler = new CreditsResponseHandler();
          $result = $handler->handle($body);
          break;

        case self::ENDPOINT_DELIVERY_REPORT: // Fallthrough
        default:
          $handler = new DeliveryReportHandler();
          $result = $handler->handle($body);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResourceForCommand($command, $method = 'GET') {
    switch ($command) {
      case 'send':
        return self::ENDPOINT_SEND_ADVANCED;
      case 'report':
        return self::ENDPOINT_DELIVERY_REPORT;
      case 'credits':
      case 'test':
      default:
        return self::ENDPOINT_CREDIT_BALANCE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $handler = new DeliveryReportHandler();
    return $handler->handle($request->getContent());
  }

}
