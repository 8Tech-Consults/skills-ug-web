<?php

// Test login to get a working token
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

echo "🔐 Attempting to login to get authentication token...\n";

function loginUser($url) {
    $loginData = [
        'id' => 1,
        'password' => '4321'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url . '/api/users/login',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($loginData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Login Status Code: $httpCode\n";
    echo "Login Response: " . substr($response, 0, 500) . "\n\n";
    
    $responseData = json_decode($response, true);
    return $responseData;
}

// Try login
$loginResult = loginUser($baseUrl);

// If login successful, try to test an endpoint with the new token
if (isset($loginResult['data']['access_token'])) {
    $token = $loginResult['data']['access_token'];
    echo "✅ Login successful! Got token: " . substr($token, 0, 20) . "...\n\n";
    
    // Test learning dashboard with new token
    echo "🧪 Testing learning dashboard with login token...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/api/learning/dashboard',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Dashboard Status: $httpCode\n";
    echo "Dashboard Response: " . substr($response, 0, 300) . "\n";
    
} else {
    echo "❌ Login failed. Trying alternative approaches...\n\n";
    
    // Let's also try the mobile app style POST with form data
    echo "🔐 Trying mobile app style login...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/api/users/login',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['id' => 1, 'password' => '4321']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Form Login Status: $httpCode\n";
    echo "Form Login Response: " . substr($response, 0, 500) . "\n";
}
