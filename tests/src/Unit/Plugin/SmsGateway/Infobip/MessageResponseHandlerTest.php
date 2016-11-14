<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\Infobip;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
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
  public function testHandleMethod($raw, $expected_message_count, $expected_result) {
    $handler = new MessageResponseHandler();
    $result = $handler->handle($raw);
    $this->assertEquals($expected_message_count, count($result->getReports()));
    $this->assertEquals($expected_result, $result);
  }

  public function providerMessageResponseHandler() {
    return [
      [
        MessageResponseHandlerTestFixtures::$testMessageResponse1,
        3,
        (new SmsMessageResult())
          ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'))
          ->setReports([
            '41793026727' => (new SmsDeliveryReport())
              ->setStatus(SmsMessageReportStatus::QUEUED)
              ->setStatusMessage('Message accepted')
              ->setRecipient('41793026727')
              ->setMessageId('MESSAGE-ID-123-xyz'),

            '41793026731' => (new SmsDeliveryReport())
              ->setStatus(SmsMessageReportStatus::QUEUED)
              ->setStatusMessage('Message accepted')
              ->setRecipient('41793026731')
              ->setMessageId('9304a5a3ab19-1ca1-be74-76ad87651ed25f35'),

            '41793026785' => (new SmsDeliveryReport())
              ->setStatus(SmsMessageReportStatus::QUEUED)
              ->setStatusMessage('Message accepted')
              ->setRecipient('41793026785')
              ->setMessageId('5f35f87a2f19-a141-43a4-91cd81b85f8c689'),
          ]),
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
