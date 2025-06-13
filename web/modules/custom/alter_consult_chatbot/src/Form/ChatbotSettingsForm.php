<?php

namespace Drupal\alter_consult_chatbot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Alter Consult Chatbot settings.
 */
class ChatbotSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alter_consult_chatbot_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['alter_consult_chatbot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alter_consult_chatbot.settings');

    $form['openai'] = [
      '#type' => 'details',
      '#title' => $this->t('OpenAI Settings'),
      '#open' => TRUE,
    ];

    $form['openai']['openai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $config->get('openai_api_key'),
      '#required' => TRUE,
    ];

    $form['openai']['openai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Model'),
      '#options' => [
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        'gpt-4' => 'GPT-4',
      ],
      '#default_value' => $config->get('openai_model') ?: 'gpt-3.5-turbo',
      '#required' => TRUE,
    ];

    $form['chatbot'] = [
      '#type' => 'details',
      '#title' => $this->t('Chatbot Settings'),
      '#open' => TRUE,
    ];

    $form['chatbot']['initial_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial Message'),
      '#default_value' => $config->get('initial_message') ?: 'Hello! I\'m your AI assistant. How can I help you with your project today?',
      '#required' => TRUE,
    ];

    $form['chatbot']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Chatbot Position'),
      '#options' => [
        'bottom-right' => $this->t('Bottom Right'),
        'bottom-left' => $this->t('Bottom Left'),
      ],
      '#default_value' => $config->get('position') ?: 'bottom-right',
    ];

    $form['chatbot']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Chatbot Theme'),
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('theme') ?: 'light',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('alter_consult_chatbot.settings')
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->set('openai_model', $form_state->getValue('openai_model'))
      ->set('initial_message', $form_state->getValue('initial_message'))
      ->set('position', $form_state->getValue('position'))
      ->set('theme', $form_state->getValue('theme'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}