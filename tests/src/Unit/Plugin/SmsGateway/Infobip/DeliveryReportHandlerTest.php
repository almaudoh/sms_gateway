<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\Infobip;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;
use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\DeliveryReportHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler, XmlResponseHandler, etc.
 *
 * @group SMS Gateway
 */
class DeliveryReportHandlerTest extends UnitTestCase {

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod($raw, $expected_message_count, array $expected_result) {
    $handler = new DeliveryReportHandler();
    /** @var \Drupal\sms\Message\SmsDeliveryReportInterface[] $reports */
    $reports = $handler->handle($raw, 'test_gateway');
    $this->assertEquals($expected_message_count, count($reports));
    $this->assertEquals($expected_result, $reports);
  }

  public function providerMessageResponseHandler() {
    $defaults = [
      'status' => TRUE,
      'message' => '',
      'report' => []
    ];
    $report1 = [
      'status' => SmsDeliveryReportInterface::STATUS_SENT,
      'delivered_time' => REQUEST_TIME,
      'send_time' => REQUEST_TIME,
      'error_code' => 0,
      'error_message' => '',
      'gateway_status' => 'SENT',
      'gateway_error_code' => '',
      'gateway_error_message' => '',
    ];
    return [
      [
        DeliveryReportHandlerTestFixtures::$testDeliveryReport1,
        2,
        [
          new SmsDeliveryReport([
              'recipient' => '41793026731',
              'message_id' => 'bcfb828b-7df9-4e7b-8715-f34f5c61271a',
              'bulk_id' => '80664c0c-e1ca-414d-806a-5caf146463df',
              'status' => SmsDeliveryReportInterface::STATUS_DELIVERED,
              'delivered_time' => "2015-02-12T09:51:43.127+0100",
              'send_time' => "2015-02-12T09:51:43.123+0100",
              'error_code' => SmsGatewayPluginInterface::STATUS_OK,
              'error_message' => 'No Error',
              'gateway_status' => 'DELIVERED_TO_HANDSET',
              'gateway_error_code' => 0,
              'gateway_error_message' => 'No Error',
            ] + $report1),
          // @TODO Change this second one to a failed report.
          new SmsDeliveryReport([
              'recipient' => '41793026727',
              'message_id' => '12db39c3-7822-4e72-a3ec-c87442c0ffc5',
              'bulk_id' => '08fe4407-c48f-4d4b-a2f4-9ff583c985b8',
              'status' => SmsDeliveryReportInterface::STATUS_DELIVERED,
              'delivered_time' => "2015-02-12T09:50:22.232+0100",
              'send_time' => "2015-02-12T09:50:22.221+0100",
              'error_code' => SmsGatewayPluginInterface::STATUS_OK,
              'error_message' => 'No Error',
              'gateway_status' => 'DELIVERED_TO_HANDSET',
              'gateway_error_code' => 0,
              'gateway_error_message' => 'No Error',
            ] + $report1),
        ],
      ],
    ];
  }
}

class DeliveryReportHandlerTestFixtures {

  public static $testDeliveryReport1 =<<<EOF
{
   "results":[
      {
         "bulkId":"80664c0c-e1ca-414d-806a-5caf146463df",
         "messageId":"bcfb828b-7df9-4e7b-8715-f34f5c61271a",
         "to":"41793026731",
         "sentAt":"2015-02-12T09:51:43.123+0100",
         "doneAt":"2015-02-12T09:51:43.127+0100",
         "smsCount":1,
         "mccMnc": "22801",
         "price":{
            "pricePerMessage":0.01,
            "currency":"EUR"
         },
         "callbackData": "User defined data.",
         "status":{
            "groupId":3,
            "groupName":"DELIVERED",
            "id":5,
            "name":"DELIVERED_TO_HANDSET",
            "description":"Message delivered to handset"
         },
         "error":{
            "groupId":0,
            "groupName":"OK",
            "id":0,
            "name":"NO_ERROR",
            "description":"No Error",
            "permanent":false
         }
      },
      {
         "bulkId":"08fe4407-c48f-4d4b-a2f4-9ff583c985b8",
         "messageId":"12db39c3-7822-4e72-a3ec-c87442c0ffc5",
         "to":"41793026727",
         "sentAt":"2015-02-12T09:50:22.221+0100",
         "doneAt":"2015-02-12T09:50:22.232+0100",
         "smsCount":1,
         "mccMnc": "22801",
         "price":{
            "pricePerMessage":0.01,
            "currency":"EUR"
         },
         "callbackData": "reset_password",
         "status":{
            "groupId":3,
            "groupName":"DELIVERED",
            "id":5,
            "name":"DELIVERED_TO_HANDSET",
            "description":"Message delivered to handset"
         },
         "error":{
            "groupId":0,
            "groupName":"OK",
            "id":0,
            "name":"NO_ERROR",
            "description":"No Error",
            "permanent":false
         }
      }
   ]
}
EOF;

}

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', 1234567890);
}
