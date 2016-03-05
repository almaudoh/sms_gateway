<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\RouteSms;

use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;

/**
 * Handles delivery reports for the RouteSMS Gateway.
 */
class DeliveryReportHandler {

  /**
   * Processes RouteSMS delivery reports into SMS delivery report objects.
   *
   * @param array $post
   *   An array containing delivery information on the message.
   * @param string $gateway_name
   *   The gateway config entity name.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   */
  public function parseDeliveryReport($post, $gateway_name) {
    return new SmsDeliveryReport([
      'recipient' => $_POST['sMobileNo'],
      'message_id' => trim($post['sMessageId']),
      'time_sent' => date_create_from_format("Y-m-d H:i:s", $post['dtSubmit'], timezone_open('UTC'))->getTimestamp(),
      'time_delivered' => date_create_from_format("Y-m-d H:i:s", $post['dtDone'], timezone_open('UTC'))->getTimestamp(),
      'status' => self::$statusMap[$post['sStatus']],
      'gateway' => $gateway_name,
      'gateway_status' => $post['sStatus'],
      'gateway_status_code' => $post['sStatus'],
      'gateway_status_description' => $post['sStatus'],
    ]);
  }

  // 'UNKNOWN', 'ACKED', 'ENROUTE', 'DELIVRD', 'EXPIRED', 'DELETED',
  // 'UNDELIV', 'ACCEPTED', 'REJECTD'.
  protected static $statusMap = [
    'DELIVRD' => SmsDeliveryReportInterface::STATUS_DELIVERED,
    'UNDELIV' => SmsDeliveryReportInterface::STATUS_NOT_DELIVERED,
    'REJECTD' => SmsDeliveryReportInterface::STATUS_REJECTED,
    'EXPIRED' => SmsDeliveryReportInterface::STATUS_EXPIRED,
    'UNKNOWN' => SmsDeliveryReportInterface::STATUS_UNKNOWN,
    'ACKED'   => SmsDeliveryReportInterface::STATUS_QUEUED,
    'ENROUTE' => SmsDeliveryReportInterface::STATUS_SENT,
    'DELETED' => SmsDeliveryReportInterface::STATUS_NOT_DELIVERED,
    'ACCEPTED' => SmsDeliveryReportInterface::STATUS_QUEUED,
  ];

}
