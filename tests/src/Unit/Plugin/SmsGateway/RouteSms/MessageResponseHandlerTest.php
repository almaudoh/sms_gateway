<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\RouteSms;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\MessageResponseHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler.
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
    $this->assertEquals($expected_message_count, count($result['report']));
    $this->assertEquals($expected_result, $result);
  }

  public function providerMessageResponseHandler() {
    $defaults = [
      'status' => TRUE,
      'message' => '',
      'report' => []
    ];
    //
    //
    $report1 = [
      'status' => SmsDeliveryReportInterface::STATUS_SENT,
      'time_sent' => REQUEST_TIME,
      'time_delivered' => REQUEST_TIME,
      'error_code' => 0,
      'error_message' => '',
      'gateway_status' => 'SENT',
      'gateway_error_code' => '',
      'gateway_error_message' => '',
    ];
    return [
      [
        '1701|123123123|bc5f7425-c98c-445b-a1f7-4fc5e2acef7e,1701|234234234|5122f879-2ba7-4469-8ae2-4091267ef389,1701|678678678|20cef313-1660-4b92-baa5-1b7ba45256a5',
        3,
        [
          'status' => TRUE,
          'message' => new TranslatableMarkup('Message successfully delivered.'),
          'report' => [
            '123123123' => new SmsDeliveryReport(['recipient' => '123123123', 'message_id' => 'bc5f7425-c98c-445b-a1f7-4fc5e2acef7e'] + $report1),
            '234234234' => new SmsDeliveryReport(['recipient' => '234234234', 'message_id' => '5122f879-2ba7-4469-8ae2-4091267ef389'] + $report1),
            '678678678' => new SmsDeliveryReport(['recipient' => '678678678', 'message_id' => '20cef313-1660-4b92-baa5-1b7ba45256a5'] + $report1),
          ],
        ],
      ],
      [
        '1701|987987987|3023779f-1722-4b7b-a3c8-d81f9e4bfc32,1704|234509,1706|23405,1707|3453453456',
        8,
        [
          'status' => TRUE,
          'message' => new TranslatableMarkup('Message successfully delivered.'),
          'report' => [
            '987987987'  => new SmsDeliveryReport(['recipient' => '987987987', 'message_id' => '3023779f-1722-4b7b-a3c8-d81f9e4bfc32'] + $report1),
            '234509'     => new SmsDeliveryReport(['recipient' => '234234234', 'message_id' => '5122f879-2ba7-4469-8ae2-4091267ef389'] + $report1),
            '23405'      => new SmsDeliveryReport(['recipient' => '234234234', 'message_id' => '5122f879-2ba7-4469-8ae2-4091267ef389'] + $report1),
            '3453453456' => new SmsDeliveryReport(['recipient' => '678678678', 'message_id' => '20cef313-1660-4b92-baa5-1b7ba45256a5'] + $report1),
          ],
        ],
      ],
    ];
  }
}

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', 1234567890);
//  \Drupal::request()->server->get('REQUEST_TIME');
}
