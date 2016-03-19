<?php

namespace Drupal\sms_gateway\Tests\Plugin\SmsGateway;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;

/**
 * Web tests for infobip gateway plugin.
 *
 * @group SMS Gateways
 */
class InfobipGatewayTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway'];
	
  /**
   * Tests Infobip API directly.
   */
  public function testApi() {
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
        // @todo Need tests with delivery reports on.
				'reports' => FALSE,
			],
		]);

		// Test gateway and ensure we actually have an answer. Expecting an
    // authentication failure since the username / password don't exist.
    $sms_message = new SmsMessage($this->randomMachineName(), ['234234234234'], 'test message');
    $response = $gateway->getPlugin()->send($sms_message, []);

    // Expect the request to fail because of authentication failure.
    $this->assertFalse($response->getStatus());
    $this->assertEqual($response->getErrorMessage(), 'An error occurred during the HTTP request: (401) Client error: 401');

    // @todo More tests with valid credentials.
  }

  /**
   * Tests creating a gateway using the default configuration form.
   */
  public function testConfigurationForm() {
    $this->drupalLogin($this->rootUser);
    $edit = [
      'label' => $this->randomString(),
      'status' => 1,
      'plugin_id' => 'infobip',
      'id' => strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(new Url('entity.sms_gateway.add'), $edit, 'Save');
    $this->assertResponse(200);
    $this->assertUrl(new Url('entity.sms_gateway.edit_form', ['sms_gateway' => $edit['id']]));
    // Assert default value of port field.
    $this->assertFieldByName('port', 80);

    $settings = [
      'ssl' => FALSE,
      'server' => 'api.infobip.com',
      'port' => '',
      'username' => 'test_user',
      'password' => 'password',
      'reports' => FALSE,
    ];
    $this->drupalPostForm(NULL, $settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('An error occurred during the HTTP request: (401) Client error: 401.');
  }

}
