<?php

// Simple test to verify authentication and routes
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Load environment
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8888/skills-ug-web';
$token = $_ENV['JWT_TESTING_TOKEN'] ?? '';

echo "🧪 Quick Authentication Test\n";
echo "🌐 Base URL: $baseUrl\n";
echo "🔑 Token: " . substr($token, 0, 30) . "...\n\n";

function testEndpoint($url, $token) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true) ?: $response
    ];
}

// Test available routes first
$testRoutes = [
    'learning/dashboard',
    'users/me', 
    'learning/my-subscriptions',
    'learning/certificates'
];

foreach ($testRoutes as $route) {
    echo "Testing: {$route}\n";
    $result = testEndpoint("{$baseUrl}/api/{$route}", $token);
    echo "Status: {$result['code']}\n";
    
    if (is_array($result['response'])) {
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo "Response: " . substr($result['response'], 0, 200) . "...\n";
    }
    echo str_repeat('-', 50) . "\n\n";
}

echo "✅ Quick test completed!\n";
