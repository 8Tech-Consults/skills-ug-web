<?php

// Simple test for chat system functionality
echo "=== Testing Chat System ===\n\n";

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel Application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChatHead;
use App\Models\ChatMessage;
use App\Models\User;

try {
    // Test users
    $user1 = User::find(1); // Muhindo Mubaraka
    $user2 = User::find(83); // Lucas Nelson
    
    if (!$user1 || !$user2) {
        echo "Error: Test users not found!\n";
        exit(1);
    }
    
    echo "Testing with users:\n";
    echo "User 1: {$user1->name} (ID: {$user1->id})\n";
    echo "User 2: {$user2->name} (ID: {$user2->id})\n\n";
    
    // Test 1: Create/Get ChatHead
    echo "1. Testing ChatHead creation...\n";
    $chatHead = ChatHead::getOrCreateChatHead($user1->id, $user2->id);
    echo "✓ ChatHead created: ID={$chatHead->id}, chat_id={$chatHead->chat_id}\n";
    echo "  User1: {$chatHead->user1_id}, User2: {$chatHead->user2_id}\n";
    echo "  Type: {$chatHead->chat_type}, Active: " . ($chatHead->is_active ? 'Yes' : 'No') . "\n\n";
    
    // Test 2: Send message
    echo "2. Testing message sending...\n";
    $message = ChatMessage::createMessage(
        $chatHead->chat_id,
        $user1->id,
        $user2->id,
        "Hello! This is a test message from the chat system.",
        'text'
    );
    echo "✓ Message sent: ID={$message->id}, message_id={$message->message_id}\n";
    echo "  Content: {$message->message_content}\n";
    echo "  From: {$message->sender_id} → To: {$message->receiver_id}\n";
    echo "  Delivered: " . ($message->is_delivered ? 'Yes' : 'No') . "\n\n";
    
    // Test 3: Check updated ChatHead
    echo "3. Testing ChatHead update after message...\n";
    $chatHead->refresh();
    echo "✓ ChatHead updated:\n";
    echo "  Last message: {$chatHead->last_message}\n";
    echo "  Last message user: {$chatHead->last_message_user_id}\n";
    echo "  Unread count (User1): {$chatHead->unread_count_user1}\n";
    echo "  Unread count (User2): {$chatHead->unread_count_user2}\n\n";
    
    // Test 4: Get chat partner
    echo "4. Testing chat partner retrieval...\n";
    $partner1 = $chatHead->getChatPartner($user1->id);
    $partner2 = $chatHead->getChatPartner($user2->id);
    echo "✓ Partner for User1: {$partner1['name']} (ID: {$partner1['id']})\n";
    echo "✓ Partner for User2: {$partner2['name']} (ID: {$partner2['id']})\n\n";
    
    // Test 5: Get messages
    echo "5. Testing message retrieval...\n";
    $messages = ChatMessage::getChatMessages($chatHead->chat_id, 10);
    echo "✓ Retrieved " . count($messages) . " message(s)\n";
    foreach ($messages as $msg) {
        echo "  - {$msg->sender_id}: {$msg->message_content} ({$msg->created_at})\n";
    }
    echo "\n";
    
    // Test 6: Mark as read
    echo "6. Testing mark as read...\n";
    $chatHead->markAsRead($user2->id);
    $chatHead->refresh();
    echo "✓ Marked as read for User2\n";
    echo "  Unread count (User2): {$chatHead->unread_count_user2}\n\n";
    
    // Test 7: Get user chats
    echo "7. Testing getUserChats...\n";
    $userChats = ChatHead::getUserChats($user1->id);
    echo "✓ User1 has " . count($userChats) . " chat(s)\n";
    foreach ($userChats as $chat) {
        $partner = $chat->getChatPartner($user1->id);
        echo "  - Chat with {$partner['name']}: \"{$chat->last_message}\" (Unread: {$chat->getUnreadCount($user1->id)})\n";
    }
    echo "\n";
    
    echo "=== All tests completed successfully! ===\n";
    echo "✓ ChatHead creation works\n";
    echo "✓ Message sending works\n";
    echo "✓ ChatHead updates work\n";
    echo "✓ Partner retrieval works\n";
    echo "✓ Message retrieval works\n";
    echo "✓ Mark as read works\n";
    echo "✓ User chats retrieval works\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
