<?php

// Test send-message endpoint directly
echo "=== Testing Send Message API Endpoint ===\n\n";

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel Application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\ChatController;
use Illuminate\Http\Request;
use App\Models\User;

try {
    echo "1. Testing sendMessageLegacy endpoint with correct format...\n";
    
    $controller = new ChatController();
    $request = new Request([
        'sender_id' => 1,
        'receiver_id' => 83,
        'message' => 'Test message for format validation',
        'message_type' => 'text'
    ]);
    
    $response = $controller->sendMessageLegacy($request);
    $responseContent = json_decode($response->getContent(), true);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response format check:\n";
    
    if (isset($responseContent['code'])) {
        echo "✓ Uses 'code' field: " . $responseContent['code'] . "\n";
        
        if ($responseContent['code'] == 1) {
            echo "✓ Success code is 1 (correct)\n";
            
            if (isset($responseContent['message'])) {
                echo "✓ Has message field: " . $responseContent['message'] . "\n";
            } else {
                echo "❌ Missing message field\n";
            }
            
            if (isset($responseContent['data'])) {
                echo "✓ Has data field with message details\n";
                $data = $responseContent['data'];
                echo "   Message ID: " . ($data['id'] ?? 'missing') . "\n";
                echo "   Chat Head ID: " . ($data['chat_head_id'] ?? 'missing') . "\n";
                echo "   Message: " . ($data['message'] ?? 'missing') . "\n";
            } else {
                echo "❌ Missing data field\n";
            }
        } else {
            echo "❌ Error code: " . $responseContent['code'] . "\n";
            echo "❌ Error message: " . ($responseContent['message'] ?? 'no message') . "\n";
        }
    } else if (isset($responseContent['success'])) {
        echo "❌ Still using old 'success' field instead of 'code'\n";
    } else {
        echo "❌ Response format is invalid\n";
    }
    
    echo "\nFull response:\n";
    echo json_encode($responseContent, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";

?>
