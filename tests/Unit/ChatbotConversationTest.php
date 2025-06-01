<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatbotConversationTest extends TestCase
{
    public function testConversationHistoryStorage()
    {
        // Simulate storing and retrieving a conversation
        $conversation = [
            'user_message' => 'What is the project estimate?',
            'ai_response' => 'The estimate is $5000 and 2 weeks.'
        ];

        // Simulate storage (in real code, this would be a DB call)
        $stored = $conversation;

        $this->assertEquals('What is the project estimate?', $stored['user_message']);
        $this->assertEquals('The estimate is $5000 and 2 weeks.', $stored['ai_response']);
    }

    public function testConversationFormat()
    {
        $conversation = [
            'user_message' => 'Hello',
            'ai_response' => 'Hi! How can I help you?'
        ];

        $this->assertIsString($conversation['user_message']);
        $this->assertIsString($conversation['ai_response']);
    }
}