<?php

namespace Drupal\sms_gateway\Plugin\SmsGateway\RouteSms;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsDeliveryReportInterface;
use Drupal\sms\Plugin\SmsGatewayPluginInterface;

/**
 * Normalizes responses and reports from RouteSMS gateway.
 */
class MessageResponseHandler {

  /**
   * Handles the message response.
   *
   * @param string $body
   *   The body of the response from the gateway.
   * @param string $gateway_name
   *   The name of the gateway instance that sent the message.
   *
   * @return array
   *   A structured key-value array containing the processed result.
   */
  public function handle($body) {
    $result = [
      'status' => FALSE,
      'error_message' => new TranslatableMarkup('There was a problem with the request'),
      'reports' => [],
    ];
    if ($body) {
      // Sample response formats.
      // 1701|2348055494143|bc5f7425-c98c-445b-a1f7-4fc5e2acef7e,
      // 1701|2348134496448|5122f879-2ba7-4469-8ae2-4091267ef389,
      // 1701|2349876543|20cef313-1660-4b92-baa5-1b7ba45256a5
      // 1701|2348055494143|3023779f-1722-4b7b-a3c8-d81f9e4bfc32,1706|23405
      // 1704|2348055494143,1704|23405,1704|234509
      // 1707|2348055494143,1706|23405,1707|234509
      // Check for RouteSMS errors.
      $response = explode(',', $body);
      // Assume 4-digit codes.
      $first_code = substr($response[0], 0, 4);
      if (count($response) < 2 && ($error = $this->checkError($first_code)) !== FALSE) {
        $result['error_message'] = $error['description'];
      }
      else {
        // Message Submitted Successfully, in this case response format is:
        // 1701|<CELL_NO>|{<MESSAGE ID>},<ERROR CODE>|<CELL_NO>|{<MESSAGE ID>},...
        $result['status'] = TRUE;
        $result['error_message'] = new TranslatableMarkup('Message submitted successfully');
        foreach ($response as $data) {
          $info = explode('|', $data);
          $error = $this->checkError($info[0]);
          $result['reports'][$info[1]] = new SmsDeliveryReport([
            'recipient' => $info[1],
            'status' => $error ? SmsDeliveryReportInterface::STATUS_REJECTED : SmsDeliveryReportInterface::STATUS_SENT,
            'message_id' => isset($info[2]) ? $info[2] : '',
            'error_code' => $error ? static::$errorMap[$info[0]] : 0,
            // @todo: Should we standardize the error messages?
            'error_message' => $error ? $error['description'] : '',
            'gateway_error_code' => $error ? $error['code'] : '',
            'gateway_error_message' => $error ? $error['description'] :'',
          ]);
        }
      }
    }
    return $result;
  }

  /**
   * Checks if there is an error based on the response code supplied.
   *
   * @param string $code
   *   The response code.
   *
   * @return array|false
   *   Returns FALSE if there is no error, otherwise it returns an array with the
   *   error code (number or text) and description if there is an error.
   */
  protected function checkError($code) {
    $error_map = self::getErrorCodes();
    return array_key_exists($code, $error_map)
      ? ['code' => $code, 'description' => $error_map[$code]]
      : FALSE;
  }

  /**
   * Returns the possible error codes and messages from the gateway.
   *
   * @return array
   *   An array of the possible error codes and corresponding messages.
   */
  protected static function getErrorCodes() {
    return [
      '1702' => new TranslatableMarkup('Invalid URL Error, This means that one of the parameters was not provided or left blank'),
      '1703' => new TranslatableMarkup('Invalid value in username or password field'),
      '1704' => new TranslatableMarkup('Invalid value in "type" field'),
      '1705' => new TranslatableMarkup('Invalid Message'),
      '1706' => new TranslatableMarkup('Invalid Destination'),
      '1707' => new TranslatableMarkup('Invalid Source (Sender)'),
      '1708' => new TranslatableMarkup('Invalid value for "dlr" field'),
      '1709' => new TranslatableMarkup('User validation failed'),
      '1710' => new TranslatableMarkup('Internal Error'),
      '1025' => new TranslatableMarkup('Insufficient Credit'),
    ];
  }

  /**
   * Mapping of RouteSMS's error codes to SMS Framework's error codes.
   *
   * @var array
   */
  protected static $errorMap = [
    '1702' => SmsGatewayPluginInterface::STATUS_ERR_INVALID_CALL,
    '1703' => SmsGatewayPluginInterface::STATUS_ERR_AUTH,
    '1704' => SmsGatewayPluginInterface::STATUS_ERR_OTHER,
    '1705' => SmsGatewayPluginInterface::STATUS_ERR_MSG_OTHER,
    '1706' => SmsGatewayPluginInterface::STATUS_ERR_DEST_NUMBER,
    '1707' => SmsGatewayPluginInterface::STATUS_ERR_SRC_NUMBER,
    '1708' => SmsGatewayPluginInterface::STATUS_ERR_OTHER,
    '1709' => SmsGatewayPluginInterface::STATUS_ERR_AUTH,
    '1710' => SmsGatewayPluginInterface::STATUS_UNKNOWN,
    '1025' => SmsGatewayPluginInterface::STATUS_ERR_CREDIT,
  ];

}
