<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

$base_url = 'http://localhost:8888/skills-ug-web';
$client = new Client([
    'base_uri' => $base_url,
    'timeout' => 10.0,
]);

echo "=== Testing create-chat-head endpoint ===\n\n";

try {
    // Test the create-chat-head endpoint that the mobile app should be calling
    $response = $client->post('/api/create-chat-head', [
        'form_params' => [
            'sender_id' => 1,
            'receiver_id' => 83,
            'receiver_name' => 'Lucas Nelson',
            'logged_in_user_id' => 1
        ]
    ]);

    $data = json_decode($response->getBody()->getContents(), true);
    
    if ($data['code'] == 1) {
        echo "✓ create-chat-head successful\n";
        echo "Chat ID: " . $data['data']['id'] . "\n";
        echo "Partner: " . ($data['data']['partner_name'] ?? '') . "\n";
    } else {
        echo "✗ create-chat-head failed: " . $data['message'] . "\n";
    }

} catch (Exception $e) {
    echo "✗ Error testing create-chat-head endpoint: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
