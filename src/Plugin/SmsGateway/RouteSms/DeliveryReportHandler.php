<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\RouteSms;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms_gateway\Plugin\SmsGateway\ResponseHandlerInterface;

/**
 * Handles delivery reports for the RouteSMS Gateway.
 */
class DeliveryReportHandler implements ResponseHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function handle($post) {
    return $this->parseDeliveryReport(Json::decode($post));
  }

  /**
   * Processes RouteSMS delivery reports into SMS delivery report objects.
   *
   * @param array $post
   *   An array containing delivery information on the message.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   */
  public function parseDeliveryReport(array $post) {
    $reports[] = (new SmsDeliveryReport())
      ->setRecipient($post['sMobileNo'])
      ->setMessageId(trim($post['sMessageId']))
      ->setTimeQueued(date_create_from_format("Y-m-d H:i:s", $post['dtSubmit'], timezone_open('UTC'))->getTimestamp())
      ->setTimeDelivered(date_create_from_format("Y-m-d H:i:s", $post['dtDone'], timezone_open('UTC'))->getTimestamp())
      ->setStatus(self::$statusMap[$post['sStatus']])
      ->setStatusMessage($post['sStatus']);
    return (new SmsMessageResult())->setReports($reports);
  }

  // 'UNKNOWN', 'ACKED', 'ENROUTE', 'DELIVRD', 'EXPIRED', 'DELETED',
  // 'UNDELIV', 'ACCEPTED', 'REJECTD'.
  protected static $statusMap = [
    'DELIVRD' => SmsMessageReportStatus::DELIVERED,
    'UNDELIV' => SmsMessageReportStatus::REJECTED,
    'REJECTD' => SmsMessageReportStatus::REJECTED,
    'EXPIRED' => SmsMessageReportStatus::EXPIRED,
    'UNKNOWN' => SmsMessageReportStatus::QUEUED,
    'ACKED'   => SmsMessageReportStatus::QUEUED,
    'ENROUTE' => SmsMessageReportStatus::QUEUED,
    'DELETED' => SmsMessageReportStatus::EXPIRED,
    'ACCEPTED' => SmsMessageReportStatus::QUEUED,
  ];

}
