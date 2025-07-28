<?php

// Test script specifically for testing duplicate request handling
$baseUrl = 'http://localhost:8000/api';
$userId = 1; // Test user ID

echo "=== GDPR Duplicate Request Test ===\n\n";

echo "Testing improved duplicate request handling...\n\n";

// Test 1: First, check current requests
echo "1. Checking current requests:\n";
$response = makeRequest('GET', "$baseUrl/gdpr/requests", ['logged_in_user_id' => $userId]);
echo "Current requests: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Try to create a duplicate export request (should be handled gracefully now)
echo "2. Attempting to create duplicate export request:\n";
$requestData = [
    'logged_in_user_id' => $userId,
    'request_type' => 'export',
    'reason' => 'Testing duplicate request handling'
];
$response = makeRequest('POST', "$baseUrl/gdpr/requests", $requestData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Try a different request type that should work
echo "3. Attempting to create a delete request (should work if no pending delete):\n";
$requestData = [
    'logged_in_user_id' => $userId,
    'request_type' => 'delete',
    'reason' => 'Testing new request type'
];
$response = makeRequest('POST', "$baseUrl/gdpr/requests", $requestData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Check all requests again
echo "4. Final check of all requests:\n";
$response = makeRequest('GET', "$baseUrl/gdpr/requests", ['logged_in_user_id' => $userId]);
echo "All requests: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

function makeRequest($method, $url, $data = []) {
    $ch = curl_init();
    
    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        return ['error' => curl_error($ch), 'http_code' => $httpCode];
    }
    
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    return $decoded ?: ['raw_response' => $response, 'http_code' => $httpCode];
}
