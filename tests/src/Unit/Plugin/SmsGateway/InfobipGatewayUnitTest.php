<?php

namespace Drupal\Tests\sms_gateway\Unit\Plugin\SmsGateway;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for infobip gateway plugin.
 *
 * @group SMS Gateways
 */
class InfobipGatewayUnitTest extends UnitTestCase {

	public $gateway = 'infobip';
	
	/**
	 * {@inheritdoc}
	 */
  public function setUp() {
    parent::setUp();

  }

  /**
   * Tests Contacts CRUD implementation via Entity API
   */
  public function testAPI() {
  	// Group for logging
    $group = 'Infobip gateway';

  	// Set up gateway
  	$gateway = sms_default_gateway();
  	$gateway->config(array(
  			'username' => GW_USERNAME,
  			'password' => GW_PASSWORD,
  			'server' => GW_SERVER,
  			'method' => GW_METHOD,
  			'ssl' => GW_SSL,
  	));
  	$gateway->save_config();
  		
  	// Send random text to 4 random phone numbers
//   	$response = $gateway->send(implode(',', $this->randomRecipients(4)), $this->randomMessage(), array('sender' => $this->randomSender()));

  	// Tests on valid and invalid recipients
  	$response = $gateway->send('08134496448,2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(), 'nopush' => 1));
  	$this->assertTrue($response['status'], t('Sending Message via @gateway', array('@gateway' => $gateway->name)), $group);
  	$this->assertFalse($response['report']['08134496448']['status'], t('Sending to invalid recipient @number', array('@number' => '08134496448')), $group);
  	$this->assertEqual($response['report']['08134496448']['message status'], GatewayInterface::STATUS_ERR_DEST_NUMBER, t('Message status for invalid recipient @number', array('@number' => '08134496448')), $group);
  	$this->assertTrue($response['report']['2348134496448']['status'], t('Sending to valid recipient @number', array('@number' => '2348134496448')), $group);
  	$this->assertEqual($response['report']['2348134496448']['message status'], SMS_MSG_STATUS_OK, t('Message status for valid recipient @number', array('@number' => '2348134496448')), $group);

  	// Test on credits command
  	$credits = $gateway->credits();
  	$this->assertTrue($credits, t('Execute "credits" command. Credits: @credits', array('@credits' => $credits)), $group);
  	 
  	// Send text using SPLIT GET method
  	$gateway->config(array('method' => INFOBIP_HTTP_GET_SPLIT));
  	$response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
  	$this->assertTrue($response['status'], t('Send SMS using SPLIT GET'));
  	 
  	// Send text using NORMAL GET method
  	$gateway->config(array('method' => INFOBIP_HTTP_GET));
  	$response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
  	$this->assertTrue($response['status'], t('Send SMS using NORMAL GET'));

  	// Send text using XML POST method
  	$gateway->config(array('method' => INFOBIP_HTTP_POST));
  	$response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
  	$this->assertTrue($response['status'], t('Send SMS using XML POST'));

  	// Tests not yet implemented
  	$response = $gateway->delivery_pull('032101822485962236');
  	debug($response, 'DLR Response');
  	$this->assertTrue(count($response), t('DeliveryReports received'), $group);
  }
}