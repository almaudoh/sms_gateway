<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\Infobip;

use Drupal\sms_gateway\Plugin\SmsGateway\Infobip\CreditsResponseHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler, XmlResponseHandler, etc.
 *
 * @group SMS Gateway
 */
class CreditsResponseHandlerTest extends UnitTestCase {

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod($raw, array $expected_result) {
    $handler = new CreditsResponseHandler();
    /** @var \Drupal\sms\Message\SmsDeliveryReportInterface[] $reports */
    $reports = $handler->handle($raw);
    $this->assertEquals($expected_result, $reports);
  }

  public function providerMessageResponseHandler() {
    return [
      [
        CreditsResponseHandlerTestFixtures::$testDeliveryReport1,
        [
          'status' => TRUE,
          'credit_balance' => 'EUR 47.79134',
          'original' => [
            'balance' => 47.79134,
            'currency' => 'EUR',
          ],
        ],
      ],
    ];
  }
}

class CreditsResponseHandlerTestFixtures {

  public static $testDeliveryReport1 =<<<EOF
{
  "balance": 47.79134,
  "currency": "EUR"
}
EOF;

}

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', 1234567890);
}
