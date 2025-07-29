<?php

// Simple test script for contact form API endpoint
// Run this in the browser: http://localhost:8888/skills-ug-web/test_contact_api.php

// Test data
$test_data = [
    'name' => 'John Doe',
    'email' => 'john.doe@test.com',
    'phone' => '+256700000000',
    'subject' => 'Test Contact Form Submission',
    'message' => 'This is a test message to verify the contact form API endpoint is working correctly.',
    'inquiry_type' => 'Technical Support',
    'company' => 'Test Company'
];

// API endpoint URL
$api_url = 'http://localhost:8888/skills-ug-web/api/contact-form-submit';

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL
curl_close($ch);

// Display results
echo "<h2>Contact Form API Test</h2>";
echo "<h3>Request Data:</h3>";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>Response (HTTP $http_code):</h3>";
if ($response) {
    $response_data = json_decode($response, true);
    if ($response_data) {
        echo "<pre>" . json_encode($response_data, JSON_PRETTY_PRINT) . "</pre>";
        
        if (isset($response_data['code']) && $response_data['code'] == 1) {
            echo "<p style='color: green;'><strong>✅ SUCCESS: Contact form API is working correctly!</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ ERROR: " . ($response_data['message'] ?? 'Unknown error') . "</strong></p>";
        }
    } else {
        echo "<pre>$response</pre>";
        echo "<p style='color: red;'><strong>❌ ERROR: Invalid JSON response</strong></p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ ERROR: No response received</strong></p>";
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>If you see a success message, the API is working correctly</li>";
echo "<li>Check your database for a new record in the 'contact_submissions' table</li>";
echo "<li>Check your email for notification messages (if configured)</li>";
echo "<li>You can run this test multiple times to test rate limiting</li>";
echo "</ol>";

echo "<p><em>Note: This is a test script. Remove it after testing.</em></p>";
?>
