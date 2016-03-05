<?php

namespace Drupal\sms_gateway\Tests\Plugin\SmsGateway;

use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;
use GuzzleHttp\Exception\ClientException;

/**
 * Web tests for infobip gateway plugin.
 *
 * @group SMS Gateways
 */
class InfobipGatewayTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway'];
	
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

		// Test gateway and ensure we actually have an answer. Expecting an
    // authentication failure since the username / password don't exist.
    $sms_message = new SmsMessage($this->randomMachineName(), ['234234234234'], 'test message');
    $response = $gateway->getPlugin()->send($sms_message, []);
    $this->assertFalse($response->getStatus());
    $this->assertEqual($response->getErrorMessage(), 'An error occurred during the HTTP request: (401) Client error: 401');

    // @todo More tests with valid credentials.
  }

}
