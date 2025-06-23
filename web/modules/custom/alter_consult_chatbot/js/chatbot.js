(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.alterConsultChatbot = {
    attach: function (context, settings) {
      $('body', context).once('alter-consult-chatbot').each(function () {
        // Add chatbot icon.
        $('body').append('<div id="chatbot-icon"><img src="/modules/custom/alter_consult_chatbot/images/chatbot-icon.svg" alt="Chatbot Icon"></div>');
        $('body').append('<div id="chatbot-window"><div id="chatbot-header">Alter Consult Bot</div><div id="chatbot-messages"></div><div id="chatbot-input"><input type="text" id="chatbot-text" placeholder="Type your message..."/><button id="chatbot-send">Send</button></div></div>');

        $('#chatbot-icon').on('click', function () {
          $('#chatbot-window').toggle();
        });

        $('#chatbot-send').on('click', function () {
          var message = $('#chatbot-text').val();
          if (message.trim() !== '') {
            $('#chatbot-messages').append('<div class="user-message">' + message + '</div>');
            $('#chatbot-text').val('');
            sendMessage(message);
          }
        });

        $('#chatbot-text').on('keypress', function (e) {
          if (e.which === 13) {
            $('#chatbot-send').click();
          }
        });

        function sendMessage(message) {
          $.ajax({
            url: Drupal.url('chatbot/message'),
            type: 'POST',
            data: JSON.stringify({ message: message }),
            contentType: 'application/json',
            success: function (response) {
              $('#chatbot-messages').append('<div class="bot-message">' + response.reply + '</div>');
              $('#chatbot-messages').scrollTop($('#chatbot-messages')[0].scrollHeight);
            }
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);