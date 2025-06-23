<?php

namespace Drupal\alter_consult_chatbot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Alter Consult Chatbot.
 */
class ChatbotController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ChatbotController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Handles chatbot messages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the chatbot's reply.
   */
  public function handleMessage(Request $request) {
    $message = json_decode($request->getContent(), TRUE)['message'];
    $config = $this->configFactory->get('alter_consult_chatbot.settings');
    $services = $config->get('services');
    $multiplier = $config->get('market_price_multiplier');

    // Simple keyword-based logic for now.
    if (str_contains(strtolower($message), 'service')) {
      $service_list = "We offer the following services: <ul>";
      foreach ($services as $service) {
        $service_list .= "<li>" . $service['name'] . ": " . $service['description'] . "</li>";
      }
      $service_list .= "</ul>";
      $reply = $service_list;
    } elseif (str_contains(strtolower($message), 'quote')) {
      $reply = 'I can help with that. Which service are you interested in for a quote?';
    } else {
        $found_service = null;
        foreach ($services as $service) {
            if (str_contains(strtolower($message), strtolower($service['name']))) {
                $found_service = $service;
                break;
            }
        }
        if ($found_service) {
            $price = $found_service['price'] * $multiplier;
            $reply = "The estimated price for " . $found_service['name'] . " is $" . number_format($price, 2) . ". This is an estimate and can be customized.";
        } else {
            $reply = "I'm sorry, I didn't understand that. You can ask me about our services or request a quote.";
        }
    }

    return new JsonResponse(['reply' => $reply]);
  }

}