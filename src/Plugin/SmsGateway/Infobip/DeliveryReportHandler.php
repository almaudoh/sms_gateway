<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsDeliveryReport;

/**
 * Handles delivery reports for the Infobip Gateway.
 */
class DeliveryReportHandler extends InfobipResponseHandlerBase {

  public function handle($body, $gateway_name) {
    return $this->parseDeliveryReport($body, $gateway_name);
  }

  /**
   * Processes Infobip delivery reports into SMS delivery report objects.
   *
   * @param $body
   * @param $gateway_name
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   */
  protected function parseDeliveryReport($body, $gateway_name) {
    $response = Json::decode($body);
    $reports = [];
    foreach ($response['results'] as $result) {
      $reports[] = new SmsDeliveryReport([
        'recipient' => $result['to'],
        'message_id' => $result['messageId'],
        'send_time' => $result['sentAt'],
        'delivered_time' => $result['doneAt'],
        'status' => $this->mapStatus($result['status']),
        'gateway' => $gateway_name,
        'gateway_status' => $result['status']['name'],
        'gateway_status_code' => $result['status']['id'],
        'gateway_status_description' => $result['status']['description'],
      ] + $this->parseError($result['error']));
    }
    return $reports;
  }

}
