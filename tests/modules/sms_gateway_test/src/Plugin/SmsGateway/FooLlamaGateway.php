<?php

namespace Drupal\sms_gateway_test\Plugin\SmsGateway;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms_gateway\Plugin\SmsGateway\DefaultGatewayPluginBase;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;

/**
 * Adds a gateway to communicate with foo llamas.
 *
 * @SmsGateway(
 *   id = "foo_llama",
 *   label = @Translation("Foo Llama Gateway"),
 * )
 */
class FooLlamaGateway extends DefaultGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function balance() {
    return 'Foo balance';
  }

  /**
   * {@inheritdoc}
   */
  protected function doCommand($command, array $data, array $config = NULL) {
    $url = $this->buildRequestUrl($command, $config);
    try {
      if ($config['simulate_error']) {
        // Throw a Guzzle exception to see how it is handled
        throw new TransferException($config['simulate_error'], $config['simulate_error_code']);
      }
      $response = $this->httpRequest($url);
      // Check for HTTP errors.
      if ($response->getStatusCode() !== 200) {
        // Add this to the errors list.
        $this->errors[] = [
          'code' => $response->getStatusCode(),
          'message' => $response->getReasonPhrase(),
        ];
        return [
          'status' => FALSE,
          'error_message' => new FormattableMarkup('An error occurred during the HTTP request: (@code) @message',
            ['@code' => $response->getStatusCode(), '@message' => $response->getReasonPhrase()]),
        ];
      }
      else {
        // Verify the response from example.com.
        if ($this->verifyResponse($response->getBody())) {
          // The response was correctly verified.
          return [
            'status' => TRUE,
            'error_message' => 'The expected response "Example Domain This domain is established to be used for illustrative examples in documents." was found'
          ];
        }
        else {
          // Return false if the response did not verify.
          return [
            'status' => FALSE,
            'error_message' => 'The expected response "Example Domain This domain is established to be used for illustrative examples in documents." was not found'
          ];
        }
      }
    }
    catch (GuzzleException $e) {
      // This error should not get to the user.
      $t_args = ['@code' => $e->getCode(), '@message' => $e->getMessage()];
      $this->logger()->error('An error occurred during the HTTP request: (@code) @message', $t_args);
      $this->errors[] = [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ];
      return [
        'status' => FALSE,
        'error_message' => $this->t('An error occurred during the HTTP request: (@code) @message', $t_args),
      ];
    }
  }

  protected function verifyResponse($body) {
    return strpos($body, 'This domain is established to be used for illustrative examples in documents.') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResourceForCommand($command) {
    return '/';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['simulate_error'] = [
      '#type' => 'textfield',
      '#title' => 'Simulate Error',
      '#default_value' => '',
    ];
    $form['simulate_error_code'] = [
      '#type' => 'number',
      '#title' => 'Simulate Error Code',
      '#default_value' => '0',
    ];

    return $form;
  }

}
