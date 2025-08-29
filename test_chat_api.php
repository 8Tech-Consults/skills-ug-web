<?php

// Test API endpoints directly
echo "=== Testing Chat API Endpoints ===\n\n";

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel Application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\ChatController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    echo "1. Testing legacy getMyChats endpoint...\n";
    
    // Mock authentication by setting the user
    $user = User::find(1);
    Auth::login($user);
    
    $controller = new ChatController();
    $request = new Request(['user_id' => 1]);
    
    $response = $controller->getMyChats($request);
    $data = $response;
    
    if (is_array($data) || $data instanceof \Illuminate\Support\Collection) {
        echo "✓ getMyChats returned " . count($data) . " chats\n";
        if (count($data) > 0) {
            $firstChat = $data[0];
            echo "First chat: ID={$firstChat['id']}, Partner={$firstChat['receiver_name']}, Last Message: {$firstChat['last_message']}\n";
        }
    } else {
        echo "❌ getMyChats returned invalid format\n";
        echo "Response type: " . gettype($data) . "\n";
        if (is_object($data)) {
            echo "Object class: " . get_class($data) . "\n";
        }
        print_r($data);
    }
    
    echo "\n2. Testing createOrGetChatHead endpoint...\n";
    
    $createRequest = new Request([
        'sender_id' => 1,
        'receiver_id' => 83,
        'receiver_name' => 'Lucas Nelson'
    ]);
    
    $createResponse = $controller->createOrGetChatHead($createRequest);
    $createData = $createResponse->getData(true);
    
    if ($createData && $createData['code'] == 1) {
        echo "✓ createOrGetChatHead successful\n";
        echo "Chat ID: {$createData['data']['id']}\n";
        echo "Partner: {$createData['data']['receiver_name']}\n";
    } else {
        echo "❌ createOrGetChatHead failed\n";
        var_dump($createData);
    }
    
    echo "\n3. Testing sendMessageLegacy endpoint...\n";
    
    $messageRequest = new Request([
        'sender_id' => 1,
        'receiver_id' => 83,
        'message' => 'Test message from API endpoint',
        'message_type' => 'text'
    ]);
    
    $messageResponse = $controller->sendMessageLegacy($messageRequest);
    $messageData = $messageResponse->getData(true);
    
    if ($messageData && $messageData['success'] == '1') {
        echo "✓ sendMessageLegacy successful\n";
        echo "Message: {$messageData['data']['message']}\n";
    } else {
        echo "❌ sendMessageLegacy failed\n";
        var_dump($messageData);
    }
    
    echo "\n4. Testing getChatMessages endpoint...\n";
    
    if (isset($createData['data']['id'])) {
        $messagesRequest = new Request([
            'user_id' => 1,
            'chat_head_id' => $createData['data']['id']
        ]);
        
        $messagesResponse = $controller->getChatMessages($messagesRequest);
        
        if ($messagesResponse->getStatusCode() == 200) {
            $messagesData = $messagesResponse->getData(true);
            
            if (isset($messagesData['success']) && $messagesData['success']) {
                $messages = $messagesData['data'] ?? [];
                echo "✓ getChatMessages returned " . count($messages) . " messages\n";
                if (count($messages) > 0) {
                    foreach ($messages as $msg) {
                        if (isset($msg['sender_id']) && isset($msg['body'])) {
                            echo "- From {$msg['sender_id']}: {$msg['body']}\n";
                        }
                    }
                }
            } else {
                echo "❌ getChatMessages API error\n";
                var_dump($messagesData);
            }
        } else {
            echo "❌ getChatMessages HTTP error: " . $messagesResponse->getStatusCode() . "\n";
        }
    }
    
    echo "\n=== API Tests Complete ===\n";

} catch (Exception $e) {
    echo "❌ API Test failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
