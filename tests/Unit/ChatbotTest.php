<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\alter_consult_chatbot\Service\OpenAIService;
use Drupal\alter_consult_chatbot\Service\ProjectEstimationService;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Logger\LoggerChannelInterface;

class ChatbotTest extends TestCase
{
    protected $httpClient;
    protected $configFactory;
    protected $loggerFactory;
    protected $config;
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock HTTP Client
        $this->httpClient = $this->createMock(ClientInterface::class);

        // Mock Config
        $this->config = $this->createMock(Config::class);
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                $config = [
                    'openai_api_key' => 'test-api-key',
                    'openai_model' => 'gpt-3.5-turbo',
                ];
                return $config[$key] ?? null;
            });

        // Mock Config Factory
        $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
        $this->configFactory->expects($this->any())
            ->method('get')
            ->willReturn($this->config);

        // Mock Logger
        $this->logger = $this->createMock(LoggerChannelInterface::class);
        $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
        $this->loggerFactory->expects($this->any())
            ->method('get')
            ->willReturn($this->logger);
    }

    public function testOpenAIServiceInitialization()
    {
        $service = new OpenAIService($this->httpClient, $this->configFactory, $this->loggerFactory);
        $this->assertInstanceOf(OpenAIService::class, $service);
    }

    public function testProcessMessage()
    {
        $service = new OpenAIService($this->httpClient, $this->configFactory, $this->loggerFactory);

        // Mock successful API response
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'choices' => [
                    ['message' => ['content' => 'Test response']]
                ]
            ])));

        $response = $service->processMessage('Test message');
        $this->assertEquals('Test response', $response);
    }

    public function testGenerateEstimate()
    {
        $service = new ProjectEstimationService($this->httpClient, $this->configFactory, $this->loggerFactory);

        // Mock successful API response
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'timeline' => '2 weeks',
                        'cost' => '$5000',
                        'resources' => ['Developer', 'Designer']
                    ])]]
                ]
            ])));

        $projectInfo = [
            'type' => 'web',
            'features' => ['blog', 'contact form'],
            'timeline' => '2 weeks'
        ];

        $estimate = $service->generateEstimate($projectInfo);
        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('timeline', $estimate);
        $this->assertArrayHasKey('cost', $estimate);
        $this->assertArrayHasKey('resources', $estimate);
    }

    public function testExtractProjectInfo()
    {
        $service = new ProjectEstimationService($this->httpClient, $this->configFactory, $this->loggerFactory);

        $conversation = [
            'user_message' => 'I need a website with blog and contact form',
            'ai_response' => 'I understand you want a website with blog and contact form. What is your timeline?'
        ];

        $info = $service->extractProjectInfo($conversation);
        $this->assertIsArray($info);
        $this->assertArrayHasKey('features', $info);
        $this->assertContains('blog', $info['features']);
        $this->assertContains('contact form', $info['features']);
    }

    public function testErrorHandling()
    {
        $service = new OpenAIService($this->httpClient, $this->configFactory, $this->loggerFactory);

        // Mock API error
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \GuzzleHttp\Exception\RequestException(
                'Error Communicating with Server',
                new \GuzzleHttp\Psr7\Request('POST', 'test')
            ));

        $this->logger->expects($this->once())
            ->method('error');

        $response = $service->processMessage('Test message');
        $this->assertEquals('Sorry, I encountered an error. Please try again later.', $response);
    }
}