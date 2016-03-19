<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;

/**
 * Normalizes send reports to the SMS Framework standard.
 */
class MessageResponseHandler extends InfobipResponseHandlerBase {

  /**
   * Handles the message response.
   *
   * @param string $body
   *   The body of the response from the gateway.
   * @param string $gateway_name
   *   The name of the gateway instance that sent the message.
   *
   * @return array
   *   A structured key-value array containing the processed result.
   */
  public function handle($body) {
    $response = Json::decode($body);
    if ($response['messages']) {
      $result = [
        'status' => TRUE,
        'error_message' => new TranslatableMarkup('Message successfully delivered.'),
        'reports' => [],
      ];
      foreach ($response['messages'] as $message) {
        $result['reports'][$message['to']] = new SmsDeliveryReport([
          'recipient' => $message['to'],
          'status' => $this->mapStatus($message['status']),
          'message_id' => $message['messageId'],
        ] + (isset($message['error']) ? $this->parseError($message['error']) : []));
      }
    }
    else {
      $result = [
        // @todo should we check the HTTP response code?
        'status' => FALSE,
        'error_message' => new TranslatableMarkup('Unknown SMS Gateway error'),
        'reports' => [],
      ];
    }
    return $result;
  }

}
