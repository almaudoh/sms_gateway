<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway;


/**
 * Determines that an SmsGateway plugin has a testing functionality.
 */
interface TestableGatewayPluginInterface {

  /**
   * Tests the gateway plugin.
   *
   * @param array $config
   *    Optional configuration parameters to test gateway with.
   *
   * @return array
   *   A structured array containing information on the test:
   *   - status: true if the test was successful, false otherwise
   *   - error_message: a message describing the error if status is false
   */
  public function test(array $config = NULL);

}
