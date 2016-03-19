<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\Infobip;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\MessageResponseHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler, XmlResponseHandler, etc.
 *
 * @group SMS Gateway
 */
class MessageResponseHandlerTest extends UnitTestCase {

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod($raw, $expected_message_count, array $expected_result) {
    $handler = new MessageResponseHandler();
    /** @var \Drupal\sms\Message\SmsMessageResultInterface $result */
    $result = $handler->handle($raw, 'test_gateway');
    $this->assertEquals($expected_message_count, count($result['reports']));
    $this->assertEquals($expected_result, $result);
  }

  public function providerMessageResponseHandler() {
    $report1 = [
      'status' => SmsDeliveryReportInterface::STATUS_SENT,
      'delivered_time' => REQUEST_TIME,
      'send_time' => REQUEST_TIME,
      'error_code' => 0,
      'error_message' => '',
      'gateway_error_code' => '',
      'gateway_error_message' => '',
    ];
    return [
      [
        MessageResponseHandlerTestFixtures::$testMessageResponse1,
        3,
        [
          'status' => TRUE,
          'error_message' => new TranslatableMarkup('Message successfully delivered.'),
          'reports' => [
            '41793026727' => new SmsDeliveryReport([
                'recipient' => '41793026727',
                'message_id' => 'MESSAGE-ID-123-xyz',
              ] + $report1),
            '41793026731' => new SmsDeliveryReport([
                'recipient' => '41793026731',
                'message_id' => '9304a5a3ab19-1ca1-be74-76ad87651ed25f35',
              ] + $report1),
            '41793026785' => new SmsDeliveryReport([
                'recipient' => '41793026785',
                'message_id' => '5f35f87a2f19-a141-43a4-91cd81b85f8c689',
              ] + $report1)
          ],
        ],
      ],
    ];
  }
}

class MessageResponseHandlerTestFixtures {

  public static $testMessageResponse1 = <<<EOF
{  
   "bulkId": "BULK-ID-123-xyz",
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
         "messageId":"MESSAGE-ID-123-xyz"
      },
      {  
         "to":"41793026731",
         "status":{  
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":1,
         "messageId":"9304a5a3ab19-1ca1-be74-76ad87651ed25f35"
      },
      {  
         "to":"41793026785",
         "status":{  
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":2,
         "messageId":"5f35f87a2f19-a141-43a4-91cd81b85f8c689"
      }
   ]
}
EOF;

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

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', 1234567890);
}
