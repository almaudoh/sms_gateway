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
   *   The body of the response.
   *
   * @return array
   *   A structured array containing the credit information.
   */
  public function handle($body) {
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
      'original' => $response,
    ];
  }

}
