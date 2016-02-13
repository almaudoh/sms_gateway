<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;

/**
 * Normalizes XML send reports to the SMS Framework standard.
 */
class CreditsResponseHandler extends InfobipResponseHandlerBase {

  /**
   * Handles the message response.
   *
   * @param string $body
   * @param string[] $recipients
   * @param string $gateway_name
   *
   * @return array
   */
  public function handle($body, $gateway_name) {
    $response = Json::decode($body);
    if (!isset($response['balance'])) {
      $message = t('Not available');
    }
    else {
      $message = $response['currency'] . ' ' . $response['balance'];
    }

    // Typical response is just the credits.
    return [
      'status' => TRUE,
      'credit_balance' => $message,
      'gateway' => $gateway_name,
      'original' => $response,
    ];
  }

}
