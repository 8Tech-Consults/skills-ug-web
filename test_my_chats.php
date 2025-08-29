<?php

// Test the my-chats API response format for mobile app compatibility
echo "=== Testing My Chats API Response ===\n\n";

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel Application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\ChatController;
use Illuminate\Http\Request;
use App\Models\User;

try {
    echo "1. Testing getMyChats endpoint...\n";
    
    $controller = new ChatController();
    $request = new Request(['user_id' => 518]);
    
    $response = $controller->getMyChats($request);
    
    // Check if response is a collection or array
    if ($response instanceof \Illuminate\Support\Collection) {
        $data = $response->toArray();
    } else {
        $data = $response;
    }
    
    echo "✓ Response type: " . gettype($data) . "\n";
    echo "✓ Number of chats: " . count($data) . "\n\n";
    
    if (count($data) > 0) {
        $firstChat = $data[0];
        echo "First chat details:\n";
        echo "- ID: " . ($firstChat['id'] ?? 'missing') . "\n";
        echo "- Sender ID: " . ($firstChat['sender_id'] ?? 'missing') . "\n";
        echo "- Receiver ID: " . ($firstChat['receiver_id'] ?? 'missing') . "\n";
        echo "- Receiver Name: " . ($firstChat['receiver_name'] ?? 'NULL') . "\n";
        echo "- Last Message: " . ($firstChat['last_message'] ?? 'empty') . "\n";
        echo "- Unread Count: " . ($firstChat['unread_count'] ?? '0') . "\n";
        echo "- Chat Status: " . ($firstChat['chat_status'] ?? 'unknown') . "\n";
        echo "- Created At: " . ($firstChat['created_at'] ?? 'missing') . "\n";
        echo "- Updated At: " . ($firstChat['updated_at'] ?? 'missing') . "\n";
    }
    
    echo "\nFull JSON response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";

?>
