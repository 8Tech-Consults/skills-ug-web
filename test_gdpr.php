<?php

// Simple test script for GDPR endpoints
$baseUrl = 'http://localhost:8000/api';
$userId = 1; // Test user ID

echo "=== GDPR Endpoint Tests ===\n\n";

// Test 1: Get Consents
echo "1. Testing GET /gdpr/consents\n";
$response = makeRequest('GET', "$baseUrl/gdpr/consents", ['logged_in_user_id' => $userId]);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Get Data Summary
echo "2. Testing GET /gdpr/data-summary\n";
$response = makeRequest('GET', "$baseUrl/gdpr/data-summary", ['logged_in_user_id' => $userId]);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Get Requests
echo "3. Testing GET /gdpr/requests\n";
$response = makeRequest('GET', "$baseUrl/gdpr/requests", ['logged_in_user_id' => $userId]);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Update Consent
echo "4. Testing POST /gdpr/consents\n";
$consentData = [
    'logged_in_user_id' => $userId,
    'consent_type' => 'marketing',
    'consented' => true,
    'consent_text' => 'I agree to receive marketing communications from Skills UG.',
    'version' => '1.0'
];
$response = makeRequest('POST', "$baseUrl/gdpr/consents", $consentData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Create Request
echo "5. Testing POST /gdpr/requests\n";
$requestData = [
    'logged_in_user_id' => $userId,
    'request_type' => 'export',
    'reason' => 'I want to download my data for personal backup.'
];
$response = makeRequest('POST', "$baseUrl/gdpr/requests", $requestData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

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
