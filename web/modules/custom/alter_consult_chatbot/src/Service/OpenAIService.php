<?php

namespace Drupal\alter_consult_chatbot\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for interacting with OpenAI API.
 */
class OpenAIService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new OpenAIService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory->get('alter_consult_chatbot');
  }

  /**
   * Process a message using OpenAI API.
   *
   * @param string $message
   *   The user message to process.
   *
   * @return string
   *   The AI response.
   */
  public function processMessage($message) {
    $config = $this->configFactory->get('alter_consult_chatbot.settings');
    $api_key = $config->get('openai_api_key');
    $model = $config->get('openai_model') ?: 'gpt-3.5-turbo';

    try {
      $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            [
              'role' => 'system',
              'content' => 'You are a helpful AI assistant for Alter Consult, a web development and consulting company. Your role is to collect information about potential clients\' projects and provide initial estimates. Be professional, friendly, and thorough in gathering project requirements.',
            ],
            [
              'role' => 'user',
              'content' => $message,
            ],
          ],
          'temperature' => 0.7,
          'max_tokens' => 500,
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      return $data['choices'][0]['message']['content'];
    }
    catch (\Exception $e) {
      $this->loggerFactory->error('OpenAI API error: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Generate a project estimate based on collected information.
   *
   * @param array $project_info
   *   The collected project information.
   *
   * @return array
   *   The estimated project details.
   */
  public function generateEstimate(array $project_info) {
    $config = $this->configFactory->get('alter_consult_chatbot.settings');
    $api_key = $config->get('openai_api_key');
    $model = $config->get('openai_model') ?: 'gpt-3.5-turbo';

    try {
      $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            [
              'role' => 'system',
              'content' => 'You are an expert project estimator for Alter Consult. Based on the provided project information, generate a detailed estimate including timeline, cost range, and required resources. Format the response as JSON.',
            ],
            [
              'role' => 'user',
              'content' => json_encode($project_info),
            ],
          ],
          'temperature' => 0.3,
          'max_tokens' => 1000,
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      return json_decode($data['choices'][0]['message']['content'], TRUE);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error('OpenAI API error: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }
  }
}