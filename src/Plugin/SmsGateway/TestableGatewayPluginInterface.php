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
   * @return bool
   *   TRUE if gateway is available, FALSE otherwise.
   */
  public function test(array $config = NULL);

}
