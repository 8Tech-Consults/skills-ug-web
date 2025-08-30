<?php
// Test learning API endpoints
$base_url = 'http://localhost:8888/skills-ug-web/api';

echo "=== Testing Learning API Endpoints ===\n\n";

// Test data
$test_user_id = 1;
$test_course_id = 1;
$test_material_id = 1;
$test_unit_id = 1;

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($data && ($method == 'POST' || $method == 'PUT')) {
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'body' => $response
    ];
}

// Try login first with form data
echo "1. Testing Login (Form Data):\n";
$login_data = [
    'email' => 'mubahood360@gmail.com',
    'username' => 'mubahood360@gmail.com', 
    'password' => '4321',
    'logged_in_user_id' => '1'
];

$response = makeRequest($base_url . '/users/login', 'POST', $login_data, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
]);

echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . substr($response['body'], 0, 500) . "...\n\n";

// Parse login response to get token
$login_response_data = json_decode($response['body'], true);
$token = null;

if ($response['status_code'] == 200 && isset($login_response_data['data']['token'])) {
    $token = $login_response_data['data']['token'];
    echo "✓ Login successful! Token: " . substr($token, 0, 20) . "...\n\n";
} else {
    echo "⚠ Login failed or no token received. Testing endpoints without authentication...\n\n";
}

// Headers for authenticated requests
$auth_headers = $token ? [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
] : [
    'Accept: application/json',
    'Content-Type: application/json'
];

// Test Learning API Endpoints
$endpoints = [
    [
        'name' => 'Learning Dashboard',
        'method' => 'GET',
        'url' => '/learning/dashboard',
        'auth_required' => true
    ],
    [
        'name' => 'Get Course for Learning',
        'method' => 'GET', 
        'url' => '/learning/courses/' . $test_course_id,
        'auth_required' => true
    ],
    [
        'name' => 'Get Material Content',
        'method' => 'GET',
        'url' => '/learning/materials/' . $test_material_id,
        'auth_required' => true
    ],
    [
        'name' => 'Get My Subscriptions',
        'method' => 'GET',
        'url' => '/learning/my-subscriptions',
        'auth_required' => true
    ],
    [
        'name' => 'Get My Progress',
        'method' => 'GET',
        'url' => '/learning/my-progress',
        'auth_required' => true
    ],
    [
        'name' => 'Get Certificates',
        'method' => 'GET',
        'url' => '/learning/certificates',
        'auth_required' => true
    ],
    [
        'name' => 'Update Material Progress (POST)',
        'method' => 'POST',
        'url' => '/learning/progress',
        'data' => [
            'material_id' => $test_material_id,
            'course_id' => $test_course_id,
            'progress_percentage' => 50,
            'time_spent_seconds' => 300,
            'completed' => 'no'
        ],
        'auth_required' => true
    ],
    [
        'name' => 'Get Course Progress',
        'method' => 'GET',
        'url' => '/learning/course-progress/' . $test_course_id,
        'auth_required' => true
    ],
    [
        'name' => 'Get Material Progress',
        'method' => 'GET',
        'url' => '/learning/material-progress/' . $test_material_id,
        'auth_required' => true
    ]
];

// Test each endpoint
foreach ($endpoints as $endpoint) {
    echo "2. Testing {$endpoint['name']}:\n";
    echo "   URL: {$base_url}{$endpoint['url']}\n";
    echo "   Method: {$endpoint['method']}\n";
    
    $headers = $endpoint['auth_required'] ? $auth_headers : ['Accept: application/json'];
    $data = isset($endpoint['data']) ? json_encode($endpoint['data']) : null;
    
    if ($endpoint['method'] === 'POST' && $data) {
        $headers[] = 'Content-Type: application/json';
    }
    
    $response = makeRequest($base_url . $endpoint['url'], $endpoint['method'], $data, $headers);
    
    echo "   Status: " . $response['status_code'] . "\n";
    
    // Try to decode JSON response
    $decoded = json_decode($response['body'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "   Response: " . substr($response['body'], 0, 200) . "...\n\n";
    }
    
    // Add a small delay between requests
    usleep(100000); // 100ms
}

echo "=== Learning API Testing Complete ===\n";
