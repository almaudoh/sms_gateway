<?php

namespace Drupal\sms_gateway\Tests\Plugin\SmsGateway;

use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;

/**
 * Web tests for RouteSMS gateway plugin.
 *
 * @group SMS Gateways
 */
class RouteSmsGatewayTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway'];
	
  /**
   * Tests RouteSMS API directly.
   */
  public function testApi() {
  	// Set up gateway.
		/** @var \Drupal\sms\Entity\SmsGatewayInterface $gateway */
		$gateway = SmsGateway::create([
			'id' => $this->randomMachineName(),
			'label' => $this->randomString(),
			'plugin' => 'routesms',
			'settings' => [
				'ssl' => FALSE,
				'username' => 'test_user',
				'password' => 'password',
				'server' => 'smsplus.routesms.com',
				'port' => 80,
				'reports' => FALSE,
			],
		]);

		// Test gateway and ensure we actually have an answer.
    $sms_message = new SmsMessage($this->randomMachineName(), ['234234234234'], 'test message');
		$response = $gateway->getPlugin()->send($sms_message, []);

		// Expect the request to fail because of authentication failure.
    $this->assertFalse($response->getStatus());
    $this->assertEqual($response->getErrorMessage(), 'Invalid value in username or password field');

    // @todo More tests with valid credentials.
  }

}
