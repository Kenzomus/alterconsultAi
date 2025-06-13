(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.alterConsultChatbot = {
    attach: function (context, settings) {
      if (context !== document) {
        return;
      }

      const chatbotSettings = settings.alterConsultChatbot;
      const $body = $('body');
      let isOpen = false;
      let conversationId = null;

      // Create chatbot container
      const $chatbot = $(`
        <div class="alter-consult-chatbot ${chatbotSettings.theme}" data-position="${chatbotSettings.position}">
          <div class="chatbot-header">
            <h3>Alter Consult AI Assistant</h3>
            <button class="chatbot-toggle">×</button>
          </div>
          <div class="chatbot-messages"></div>
          <div class="chatbot-input">
            <textarea placeholder="Type your message..."></textarea>
            <button class="send-message">Send</button>
          </div>
        </div>
      `);

      // Create chatbot toggle button
      const $toggle = $(`
        <button class="chatbot-toggle-button ${chatbotSettings.theme}" data-position="${chatbotSettings.position}">
          <span class="chat-icon">💬</span>
        </button>
      `);

      // Add elements to the page
      $body.append($chatbot);
      $body.append($toggle);

      // Add initial message
      addMessage(chatbotSettings.initialMessage, 'bot');

      // Toggle chatbot
      $toggle.on('click', function () {
        isOpen = !isOpen;
        $chatbot.toggleClass('open', isOpen);
        $toggle.toggleClass('active', isOpen);
      });

      // Send message
      $('.send-message').on('click', sendMessage);
      $('.chatbot-input textarea').on('keypress', function (e) {
        if (e.which === 13 && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
        }
      });

      function sendMessage() {
        const $input = $('.chatbot-input textarea');
        const message = $input.val().trim();

        if (message) {
          addMessage(message, 'user');
          $input.val('');

          // Show typing indicator
          const $typing = $('<div class="message bot typing">...</div>');
          $('.chatbot-messages').append($typing);

          // Send message to API
          $.ajax({
            url: chatbotSettings.apiEndpoint,
            method: 'POST',
            data: {
              message: message,
              conversation_id: conversationId
            },
            success: function (response) {
              $typing.remove();
              addMessage(response.response, 'bot');
              conversationId = response.conversation_id;
            },
            error: function () {
              $typing.remove();
              addMessage('Sorry, I encountered an error. Please try again.', 'bot');
            }
          });
        }
      }

      function addMessage(message, type) {
        const $message = $(`
          <div class="message ${type}">
            <div class="message-content">${message}</div>
          </div>
        `);
        $('.chatbot-messages').append($message);
        scrollToBottom();
      }

      function scrollToBottom() {
        const $messages = $('.chatbot-messages');
        $messages.scrollTop($messages[0].scrollHeight);
      }
    }
  };
})(jQuery, Drupal);