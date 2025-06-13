<?php

namespace Drupal\alter_consult_chatbot\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for project estimation.
 */
class ProjectEstimationService {

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
   * Constructs a new ProjectEstimationService object.
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
   * Generate a detailed project estimate.
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
              'content' => 'You are an expert project estimator for Alter Consult. Based on the provided project information, generate a detailed estimate including timeline, cost range, and required resources. Consider the following factors:
                - Project scope and complexity
                - Required technologies and frameworks
                - Development phases and milestones
                - Team composition and roles
                - Infrastructure and hosting requirements
                - Maintenance and support needs
                Format the response as JSON with the following structure:
                {
                  "project_summary": "Brief project overview",
                  "timeline": {
                    "total_duration": "Estimated total duration",
                    "phases": [
                      {
                        "name": "Phase name",
                        "duration": "Phase duration",
                        "tasks": ["List of tasks"]
                      }
                    ]
                  },
                  "cost_estimate": {
                    "range": {
                      "min": "Minimum cost",
                      "max": "Maximum cost"
                    },
                    "breakdown": {
                      "development": "Development cost",
                      "design": "Design cost",
                      "testing": "Testing cost",
                      "deployment": "Deployment cost",
                      "maintenance": "Maintenance cost"
                    }
                  },
                  "team_requirements": {
                    "roles": ["List of required roles"],
                    "skills": ["List of required skills"]
                  },
                  "technical_requirements": {
                    "frontend": ["Frontend technologies"],
                    "backend": ["Backend technologies"],
                    "database": ["Database technologies"],
                    "infrastructure": ["Infrastructure requirements"]
                  },
                  "risks_and_mitigation": [
                    {
                      "risk": "Risk description",
                      "mitigation": "Mitigation strategy"
                    }
                  ]
                }',
            ],
            [
              'role' => 'user',
              'content' => json_encode($project_info),
            ],
          ],
          'temperature' => 0.3,
          'max_tokens' => 2000,
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      return json_decode($data['choices'][0]['message']['content'], TRUE);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error('Project estimation error: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Extract project information from conversation.
   *
   * @param array $conversation
   *   The conversation history.
   *
   * @return array
   *   The extracted project information.
   */
  public function extractProjectInfo(array $conversation) {
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
              'content' => 'You are an expert at extracting project requirements from conversations. Analyze the conversation and extract key project information. Format the response as JSON with the following structure:
                {
                  "project_type": "Type of project",
                  "scope": "Project scope",
                  "features": ["List of required features"],
                  "technologies": ["Preferred or required technologies"],
                  "timeline": "Expected timeline",
                  "budget": "Budget constraints if mentioned",
                  "target_audience": "Target audience",
                  "special_requirements": ["Any special requirements"]
                }',
            ],
            [
              'role' => 'user',
              'content' => json_encode($conversation),
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
      $this->loggerFactory->error('Project info extraction error: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }
  }
}