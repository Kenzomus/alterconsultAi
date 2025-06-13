<?php

namespace Drupal\alter_consult_chatbot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the chatbot dashboard.
 */
class ChatbotDashboardController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ChatbotDashboardController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Displays the chatbot dashboard.
   *
   * @return array
   *   A render array for the dashboard.
   */
  public function dashboard() {
    $build = [
      '#theme' => 'chatbot_dashboard',
      '#stats' => $this->getConversationStats(),
      '#recent_conversations' => $this->getRecentConversations(),
      '#project_estimates' => $this->getProjectEstimates(),
      '#attached' => [
        'library' => [
          'alter_consult_chatbot/chatbot-dashboard',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

  /**
   * Gets conversation statistics.
   *
   * @return array
   *   An array of conversation statistics.
   */
  protected function getConversationStats() {
    $stats = [];

    // Total conversations
    $stats['total_conversations'] = $this->database
      ->select('alter_consult_chatbot_conversations', 'c')
      ->countQuery()
      ->execute()
      ->fetchField();

    // Conversations today
    $today = strtotime('today');
    $stats['conversations_today'] = $this->database
      ->select('alter_consult_chatbot_conversations', 'c')
      ->condition('timestamp', $today, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    // Average conversation length
    $stats['avg_conversation_length'] = $this->database
      ->select('alter_consult_chatbot_conversations', 'c')
      ->fields('c', ['id'])
      ->groupBy('c.id')
      ->countQuery()
      ->execute()
      ->fetchField();

    // Unique users
    $stats['unique_users'] = $this->database
      ->select('alter_consult_chatbot_conversations', 'c')
      ->fields('c', ['uid'])
      ->distinct()
      ->countQuery()
      ->execute()
      ->fetchField();

    return $stats;
  }

  /**
   * Gets recent conversations.
   *
   * @return array
   *   An array of recent conversations.
   */
  protected function getRecentConversations() {
    return $this->database
      ->select('alter_consult_chatbot_conversations', 'c')
      ->fields('c')
      ->orderBy('c.timestamp', 'DESC')
      ->range(0, 10)
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets recent project estimates.
   *
   * @return array
   *   An array of recent project estimates.
   */
  protected function getProjectEstimates() {
    return $this->database
      ->select('alter_consult_chatbot_conversations', 'c')
      ->fields('c')
      ->condition('c.ai_response', '%"type":"project_estimate"%', 'LIKE')
      ->orderBy('c.timestamp', 'DESC')
      ->range(0, 5)
      ->execute()
      ->fetchAll();
  }
}