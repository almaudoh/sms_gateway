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
  public function handle($body, $gateway_name) {
    $response = Json::decode($body);
    if ($response['messages']) {
      $result = [
        'status' => TRUE,
        'message' => new TranslatableMarkup('Message successfully delivered.'),
        'reports' => [],
      ];
      foreach ($response['messages'] as $message) {
        $result['report'][$message['to']] = new SmsDeliveryReport([
          'recipient' => $message['to'],
          'status' => $this->mapStatus($message['status']),
          'message_id' => $message['messageId'],
          'gateway' => $gateway_name,
        ] + (isset($message['error']) ? $this->parseError($message['error']) : []));
      }
    }
    else {
      $result = [
        // @todo should we check the HTTP response code?
        'status' => FALSE,
        'message' => new TranslatableMarkup('Unknown SMS Gateway error'),
      ];
    }
    return $result;
  }

private $x = <<<EOF
{
   "messages":[
      {
         "to":"41793026727",
         "status":{
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":1,
         "messageId":"2250be2d4219-3af1-78856-aabe-1362af1edfd2"
      }
   ]
}
EOF;

}
