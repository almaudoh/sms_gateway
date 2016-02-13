<?php

namespace Drupal\Tests\sms_gateway\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;

define('DRUPAL_TEST_IN_CHILD_SITE', FALSE);

/**
 * Unit tests for infobip gateway plugin.
 *
 * @group SMS Gateways
 */
class InfobipGatewayKernelTest extends KernelTestBase {

	public static $modules = ['system', 'sms', 'sms_gateway'];
	
	/**
	 * {@inheritdoc}
	 */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
  }

  /**
   * Tests Contacts CRUD implementation via Entity API
   */
  public function testAPI() {
  	// Set up gateway.
		/** @var \Drupal\sms\Entity\SmsGatewayInterface $gateway */
		$gateway = SmsGateway::create([
			'id' => $this->randomMachineName(),
			'label' => $this->randomString(),
			'plugin' => 'infobip',
			'settings' => [
				'ssl' => FALSE,
				'username' => 'test_user',
				'password' => 'password',
				'server' => 'api.infobip.com',
				'port' => 80,
//				'reports' => TRUE,
				'reports' => FALSE,
			],
		]);

		// Test gateway and ensure we actually have an answer.
    $sms_message = new SmsMessage($this->randomMachineName(), ['234234234234'], 'test message');
		$response = $gateway->getPlugin()->send($sms_message, []);

    $this->assertEquals($response->getErrorMessage(), '');
    $this->assertEquals($response, []);

//  	// Send random text to 4 random phone numbers
////   	$response = $gateway->send(implode(',', $this->randomRecipients(4)), $this->randomMessage(), array('sender' => $this->randomSender()));
//
//  	// Tests on valid and invalid recipients
//  	$response = $gateway->send('08134496448,2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(), 'nopush' => 1));
//  	$this->assertTrue($response['status'], t('Sending Message via @gateway', array('@gateway' => $gateway->name)), $group);
//  	$this->assertFalse($response['report']['08134496448']['status'], t('Sending to invalid recipient @number', array('@number' => '08134496448')), $group);
//  	$this->assertEqual($response['report']['08134496448']['message status'], GatewayInterface::STATUS_ERR_DEST_NUMBER, t('Message status for invalid recipient @number', array('@number' => '08134496448')), $group);
//  	$this->assertTrue($response['report']['2348134496448']['status'], t('Sending to valid recipient @number', array('@number' => '2348134496448')), $group);
//  	$this->assertEqual($response['report']['2348134496448']['message status'], SMS_MSG_STATUS_OK, t('Message status for valid recipient @number', array('@number' => '2348134496448')), $group);
//
//  	// Test on credits command
//  	$credits = $gateway->credits();
//  	$this->assertTrue($credits, t('Execute "credits" command. Credits: @credits', array('@credits' => $credits)), $group);
//
//  	// Send text using SPLIT GET method
//  	$gateway->config(array('method' => INFOBIP_HTTP_GET_SPLIT));
//  	$response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
//  	$this->assertTrue($response['status'], t('Send SMS using SPLIT GET'));
//
//  	// Send text using NORMAL GET method
//  	$gateway->config(array('method' => INFOBIP_HTTP_GET));
//  	$response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
//  	$this->assertTrue($response['status'], t('Send SMS using NORMAL GET'));
//
//  	// Send text using XML POST method
//  	$gateway->config(array('method' => INFOBIP_HTTP_POST));
//  	$response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
//  	$this->assertTrue($response['status'], t('Send SMS using XML POST'));
//
//  	// Tests not yet implemented
//  	$response = $gateway->delivery_pull('032101822485962236');
//  	debug($response, 'DLR Response');
//  	$this->assertTrue(count($response), t('DeliveryReports received'), $group);
  }
}