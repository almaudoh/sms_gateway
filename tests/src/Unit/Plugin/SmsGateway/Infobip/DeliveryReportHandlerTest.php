<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\Infobip;

use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
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
    $result = $handler->handle($raw);
    $this->assertEquals($expected_message_count, count($result->getReports()));
    $this->assertEquals($expected_result, $result->getReports());
  }

  public function providerMessageResponseHandler() {
    return [
      [
        DeliveryReportHandlerTestFixtures::$testDeliveryReport1,
        2,
        [
          (new SmsDeliveryReport())
            ->setRecipient('41793026731')
            ->setMessageId('bcfb828b-7df9-4e7b-8715-f34f5c61271a')
            ->setStatus(SmsMessageReportStatus::DELIVERED)
            ->setStatusMessage('DELIVERED_TO_HANDSET')
            ->setTimeQueued('2015-02-12T09:51:43.123+0100')
            ->setTimeDelivered('2015-02-12T09:51:43.127+0100'),

          // @TODO Change this second one to a failed report.
          (new SmsDeliveryReport())
            ->setRecipient('41793026727')
            ->setMessageId('12db39c3-7822-4e72-a3ec-c87442c0ffc5')
            ->setStatus(SmsMessageReportStatus::DELIVERED)
            ->setStatusMessage('DELIVERED_TO_HANDSET')
            ->setTimeQueued("2015-02-12T09:50:22.221+0100")
            ->setTimeDelivered("2015-02-12T09:50:22.232+0100"),
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
