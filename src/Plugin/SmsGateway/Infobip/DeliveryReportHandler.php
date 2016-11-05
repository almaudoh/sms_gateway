<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageResult;

/**
 * Handles the Infobip delivery reports and turns it into SmsMessageResult.
 */
class DeliveryReportHandler extends InfobipResponseHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function handle($body) {
    $response = Json::decode($body);
    $reports = [];
    foreach ($response['results'] as $result) {
      $reports[] = (new SmsDeliveryReport())
        ->setRecipient($result['to'])
        ->setMessageId($result['messageId'])
        ->setTimeQueued($result['sentAt'])
        ->setTimeDelivered($result['doneAt'])
        ->setStatus($this->mapStatus($result['status']))
        ->setStatusMessage($result['status']['name']);
    }
    return (new SmsMessageResult())->setReports($reports);
  }

}
