<?php

namespace Drupal\sms_gateway\Tests\Plugin\SmsGateway;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Web tests for infobip gateway plugin.
 *
 * @group SMS Gateways
 */
class DefaultGatewayPluginBaseTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway', 'sms_gateway_test'];
	
  /**
   * Tests creating a gateway using the default configuration form.
   */
  public function testConfigurationForm() {
    $this->drupalLogin($this->rootUser);
    $edit = [
      'label' => $this->randomString(),
      'status' => 1,
      'plugin_id' => 'foo_llama',
      'id' => strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(new Url('entity.sms_gateway.add'), $edit, 'Save');
    $this->assertResponse(200);
    $edit_url = new Url('entity.sms_gateway.edit_form', ['sms_gateway' => $edit['id']]);
    $this->assertUrl($edit_url);
    // Assert default value of port field.
    $this->assertFieldByName('port', 80);

    $settings = [
      'ssl' => FALSE,
      'server' => 'example.com',
      // No port specified for example.com
      'port' => '',
      'username' => $this->randomMachineName(),
      'password' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('Gateway saved.');
    $this->assertText(htmlentities($edit['label']), "{$edit['label']} found");
    $this->assertUrl(new Url('sms.gateway.list'));

  	// Simulate an exception in the settings using the test gateway.
    $bad_settings = [
      'ssl' => FALSE,
      'server' => 'example.com',
      'username' => $this->randomMachineName(),
      'password' => $this->randomMachineName(),
      'simulate_error' => 'An error has been encountered...',
      'simulate_error_code' => 8430,
    ];
    $this->drupalPostForm($edit_url, $bad_settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('An error occurred during the HTTP request: (8430) An error has been encountered...');
    $this->assertUrl($edit_url);
  }

}
