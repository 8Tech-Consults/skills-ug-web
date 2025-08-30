<?php

/**
 * Comprehensive Learning API Endpoints Test Script
 * Tests all learning-related endpoints using the JWT token from .env
 */

// Load environment variables
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

// Configuration
$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8888/skills-ug-web';
$jwt_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTAuMC4yLjI6ODg4OC9za2lsbHMtdWctd2ViL2FwaS91c2Vycy9sb2dpbiIsImlhdCI6MTc1NjQ3Nzg2NiwiZXhwIjoyNzAyNTU3ODY2LCJuYmYiOjE3NTY0Nzc4NjYsImp0aSI6InJlMGxrVjhCSHRxWFRFczQiLCJzdWIiOiIxIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.InXcX75Cx7RKemsp885epmoUiaUoD04XSXvfPEDda_A';
$userId = $_ENV['TESTING_USER_ID'] ?? '1';

if (empty($jwt_token)) {
    die("❌ JWT token not found\n");
}

echo "🚀 Testing Learning API Endpoints\n";
echo "📍 Base URL: $baseUrl\n";
echo "👤 User ID: $userId\n";
echo "🔑 Using JWT Token: " . substr($jwt_token, 0, 20) . "...\n\n";

// HTTP request function
function makeRequest($url, $method = 'GET', $data = null, $token = '') {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Tok: Bearer ' . $token,
            'logged_in_user_id: 1',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        // Add logged_in_user_id to POST data like the mobile app does
        $data['logged_in_user_id'] = '1';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Test function
function testEndpoint($name, $url, $method = 'GET', $data = null, $token = '') {
    echo "🧪 Testing: $name\n";
    echo "📡 $method $url\n";
    
    if ($data) {
        echo "📤 Request data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    
    $result = makeRequest($url, $method, $data, $token);
    
    if (isset($result['error'])) {
        echo "❌ Error: " . $result['error'] . "\n";
        return false;
    }
    
    $statusIcon = $result['http_code'] >= 200 && $result['http_code'] < 300 ? '✅' : '❌';
    echo "$statusIcon Status: " . $result['http_code'] . "\n";
    
    if (!empty($result['data'])) {
        echo "📥 Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "📥 Response: " . ($result['response'] ?: 'Empty response') . "\n";
    }
    
    echo str_repeat('-', 80) . "\n\n";
    
    return $result['http_code'] >= 200 && $result['http_code'] < 300;
}

// Start testing all learning endpoints
echo "=== LEARNING API ENDPOINTS TEST ===\n\n";

// 1. Get Learning Dashboard
testEndpoint(
    'Learning Dashboard',
    "$baseUrl/api/learning/dashboard",
    'GET',
    null,
    $jwtToken
);

// 2. Get My Subscriptions
testEndpoint(
    'My Subscriptions',
    "$baseUrl/api/learning/my-subscriptions",
    'GET',
    null,
    $jwtToken
);

// 3. Get My Progress
testEndpoint(
    'My Learning Progress',
    "$baseUrl/api/learning/my-progress",
    'GET',
    null,
    $jwtToken
);

// Get a course ID first to test course-specific endpoints
echo "🔍 Getting course ID for testing...\n";
$coursesResult = makeRequest("$baseUrl/api/courses", 'GET', null, $jwtToken);
$courseId = 1; // Default to 1

if (!empty($coursesResult['data']) && is_array($coursesResult['data'])) {
    if (isset($coursesResult['data']['data']) && is_array($coursesResult['data']['data']) && count($coursesResult['data']['data']) > 0) {
        $courseId = $coursesResult['data']['data'][0]['id'];
    } elseif (isset($coursesResult['data'][0]['id'])) {
        $courseId = $coursesResult['data'][0]['id'];
    }
}
echo "📚 Using Course ID: $courseId\n\n";

// 4. Get Course for Learning
testEndpoint(
    'Course for Learning',
    "$baseUrl/api/learning/courses/$courseId",
    'GET',
    null,
    $jwtToken
);

// 5. Get Course Progress
testEndpoint(
    'Course Progress',
    "$baseUrl/api/learning/course-progress/$courseId",
    'GET',
    null,
    $jwtToken
);

// 6. Get Course Units
testEndpoint(
    'Course Units',
    "$baseUrl/api/learning/course-units/$courseId",
    'GET',
    null,
    $jwtToken
);

// Get a unit ID for material testing
echo "🔍 Getting unit ID for testing...\n";
$unitsResult = makeRequest("$baseUrl/api/learning/course-units/$courseId", 'GET', null, $jwtToken);
$unitId = 1; // Default to 1

if (!empty($unitsResult['data']) && is_array($unitsResult['data'])) {
    if (isset($unitsResult['data']['data']) && is_array($unitsResult['data']['data']) && count($unitsResult['data']['data']) > 0) {
        $unitId = $unitsResult['data']['data'][0]['id'];
    }
}
echo "📖 Using Unit ID: $unitId\n\n";

// 7. Get Unit Materials
testEndpoint(
    'Unit Materials',
    "$baseUrl/api/learning/course-materials/$unitId",
    'GET',
    null,
    $jwtToken
);

// 8. Get Unit Quizzes
testEndpoint(
    'Unit Quizzes',
    "$baseUrl/api/learning/course-quizzes/$unitId",
    'GET',
    null,
    $jwtToken
);

// Get a material ID for material testing
$materialId = 1; // Default to 1
$materialsResult = makeRequest("$baseUrl/api/learning/course-materials/$unitId", 'GET', null, $jwtToken);
if (!empty($materialsResult['data']) && is_array($materialsResult['data'])) {
    if (isset($materialsResult['data']['data']) && is_array($materialsResult['data']['data']) && count($materialsResult['data']['data']) > 0) {
        $materialId = $materialsResult['data']['data'][0]['id'];
    }
}

// 9. Get Material Content
testEndpoint(
    'Material Content',
    "$baseUrl/api/learning/materials/$materialId",
    'GET',
    null,
    $jwtToken
);

// 10. Get Material Progress
testEndpoint(
    'Material Progress',
    "$baseUrl/api/learning/material-progress/$materialId",
    'GET',
    null,
    $jwtToken
);

// 11. Update Material Progress
testEndpoint(
    'Update Material Progress',
    "$baseUrl/api/learning/progress",
    'POST',
    [
        'material_id' => $materialId,
        'progress_percentage' => 50,
        'time_spent_seconds' => 300,
        'completed' => 'no'
    ],
    $jwtToken
);

// 12. Track Material Progress
testEndpoint(
    'Track Material Progress',
    "$baseUrl/api/learning/track-progress",
    'POST',
    [
        'material_id' => $materialId,
        'time_spent' => 60,
        'progress_percentage' => 25
    ],
    $jwtToken
);

// 13. Mark Material Completed
testEndpoint(
    'Mark Material Completed',
    "$baseUrl/api/learning/mark-completed",
    'POST',
    [
        'material_id' => $materialId
    ],
    $jwtToken
);

// 14. Update Time Tracking
testEndpoint(
    'Update Time Tracking',
    "$baseUrl/api/learning/update-time",
    'POST',
    [
        'material_id' => $materialId,
        'time_spent' => 120
    ],
    $jwtToken
);

// 15. Get Certificates
testEndpoint(
    'Learning Certificates',
    "$baseUrl/api/learning/certificates",
    'GET',
    null,
    $jwtToken
);

// 16. Submit Course Review
testEndpoint(
    'Submit Course Review',
    "$baseUrl/api/learning/reviews",
    'POST',
    [
        'course_id' => $courseId,
        'rating' => 5,
        'comment' => 'Excellent course! Very informative and well-structured.'
    ],
    $jwtToken
);

// 17. Get Course Reviews
testEndpoint(
    'Get Course Reviews',
    "$baseUrl/api/learning/reviews/$courseId",
    'GET',
    null,
    $jwtToken
);

// 18. Get Notifications
testEndpoint(
    'Learning Notifications',
    "$baseUrl/api/learning/notifications",
    'GET',
    null,
    $jwtToken
);

// Get a quiz ID for quiz testing
$quizId = 1; // Default to 1
$quizzesResult = makeRequest("$baseUrl/api/learning/course-quizzes/$unitId", 'GET', null, $jwtToken);
if (!empty($quizzesResult['data']) && is_array($quizzesResult['data'])) {
    if (isset($quizzesResult['data']['data']) && is_array($quizzesResult['data']['data']) && count($quizzesResult['data']['data']) > 0) {
        $quizId = $quizzesResult['data']['data'][0]['id'];
    }
}

// 19. Submit Quiz Answer
testEndpoint(
    'Submit Quiz Answer',
    "$baseUrl/api/learning/quiz-answers",
    'POST',
    [
        'quiz_id' => $quizId,
        'question_id' => 1,
        'answer' => 'Sample answer for testing',
        'selected_option' => 'A'
    ],
    $jwtToken
);

// 20. Get Quiz Answers
testEndpoint(
    'Get Quiz Answers',
    "$baseUrl/api/learning/quiz-answers/$quizId",
    'GET',
    null,
    $jwtToken
);

// 21. Batch Update Progress
testEndpoint(
    'Batch Update Progress',
    "$baseUrl/api/learning/batch-progress",
    'POST',
    [
        'updates' => [
            [
                'material_id' => $materialId,
                'progress_percentage' => 75,
                'time_spent_seconds' => 450,
                'completed' => 'no'
            ]
        ]
    ],
    $jwtToken
);

echo "🎉 Learning API endpoints testing completed!\n";
echo "📊 Check the results above to see which endpoints are working correctly.\n";

?>
