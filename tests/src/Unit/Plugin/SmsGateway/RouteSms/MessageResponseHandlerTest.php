<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway\RouteSms;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;
use Drupal\sms_gateway\Plugin\SmsGateway\RouteSms\MessageResponseHandler;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests for MessageResponseHandler.
 *
 * @group SMS Gateway
 */
class MessageResponseHandlerTest extends UnitTestCase {

  public function setUp() {
    // Mock \Drupal::service('string_translation')::translateString() so that
    // StringTranslationTrait would work.
    $string_translation = $this->prophesize(TranslationInterface::class);
    $string_translation->translateString(Argument::type(TranslatableMarkup::class))->will(function (array $arguments) {
      /** \Drupal\Core\StringTranslation\TranslatableMarkup[] $arguments */
      return $arguments[0]->getUntranslatedString();
    });
    $container = new ContainerBuilder();
    $container->set('string_translation', $string_translation->reveal());
    \Drupal::setContainer($container);
  }

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
          'error_message' => 'Message submitted successfully',
          'reports' => [
            '123123123' => new SmsDeliveryReport(['recipient' => '123123123', 'message_id' => 'bc5f7425-c98c-445b-a1f7-4fc5e2acef7e'] + $report1),
            '234234234' => new SmsDeliveryReport(['recipient' => '234234234', 'message_id' => '5122f879-2ba7-4469-8ae2-4091267ef389'] + $report1),
            '678678678' => new SmsDeliveryReport(['recipient' => '678678678', 'message_id' => '20cef313-1660-4b92-baa5-1b7ba45256a5'] + $report1),
          ],
        ],
      ],
      [
        '1701|987987987|3023779f-1722-4b7b-a3c8-d81f9e4bfc32,1704|234509,1706|23405,1707|3453453456',
        4,
        [
          'status' => TRUE,
          'error_message' => 'Message submitted successfully',
          'reports' => [
            '987987987'  => new SmsDeliveryReport([
                'recipient' => '987987987',
                'message_id' => '3023779f-1722-4b7b-a3c8-d81f9e4bfc32'
              ] + $report1),
            '234509'     => new SmsDeliveryReport([
                'recipient' => '234509',
                'status' => SmsDeliveryReportInterface::STATUS_REJECTED,
                'error_code' => SmsGatewayPluginInterface::STATUS_ERR_OTHER,
                'error_message' => 'Invalid value in "type" field',
                'gateway_error_code' => '1704',
                'gateway_error_message' => 'Invalid value in "type" field',
              ] + $report1),
            '23405'      => new SmsDeliveryReport([
                'recipient' => '23405',
                'status' => SmsDeliveryReportInterface::STATUS_REJECTED,
                'error_code' => SmsGatewayPluginInterface::STATUS_ERR_DEST_NUMBER,
                'error_message' => 'Invalid Destination',
                'gateway_error_code' => '1706',
                'gateway_error_message' => 'Invalid Destination',
              ] + $report1),
            '3453453456' => new SmsDeliveryReport([
                'recipient' => '3453453456',
                'status' => SmsDeliveryReportInterface::STATUS_REJECTED,
                'error_code' => SmsGatewayPluginInterface::STATUS_ERR_SRC_NUMBER,
                'error_message' => 'Invalid Source (Sender)',
                'gateway_error_code' => '1707',
                'gateway_error_message' => 'Invalid Source (Sender)',
              ] + $report1),
          ],
        ],
      ],
    ];
  }
}

if (!defined('REQUEST_TIME')) {
  define('REQUEST_TIME', 1234567890);
}
