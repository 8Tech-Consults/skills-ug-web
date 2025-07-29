<?php

// Test Blog API Endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost:8888/skills-ug-web/api';

function testEndpoint($method, $endpoint, $data = [], $description = '', $expectSuccess = true) {
    global $baseUrl;
    
    $url = $baseUrl . '/' . $endpoint;
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Testing: $description\n";
    echo "Method: $method\n";
    echo "URL: $url\n";
    
    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
        echo "Full URL: $url\n";
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "CURL Error: $error\n";
        return false;
    }
    
    $decoded = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ SUCCESS\n";
        if (isset($decoded['data'])) {
            echo "Response Data Type: " . gettype($decoded['data']) . "\n";
            if (is_array($decoded['data']) && !empty($decoded['data'])) {
                if (isset($decoded['data']['data'])) {
                    echo "Items Count: " . count($decoded['data']['data']) . "\n";
                    if (!empty($decoded['data']['data'])) {
                        $firstItem = $decoded['data']['data'][0];
                        echo "Sample Item Keys: " . implode(', ', array_keys($firstItem)) . "\n";
                    }
                } else {
                    echo "Items Count: " . count($decoded['data']) . "\n";
                    if (!empty($decoded['data']) && is_array($decoded['data'][0] ?? null)) {
                        $firstItem = $decoded['data'][0];
                        echo "Sample Item Keys: " . implode(', ', array_keys($firstItem)) . "\n";
                    }
                }
            } elseif (is_array($decoded['data']) && isset($decoded['data']['title'])) {
                echo "Blog Post: " . $decoded['data']['title'] . "\n";
                echo "Slug: " . $decoded['data']['slug'] . "\n";
                echo "Category: " . ($decoded['data']['category'] ?? 'N/A') . "\n";
            }
        }
        echo "Message: " . ($decoded['message'] ?? 'No message') . "\n";
    } else {
        echo "❌ ERROR\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
    
    return $httpCode >= 200 && $httpCode < 300;
}

echo "🚀 Blog API Testing Started\n";

// Test 1: Get all blog posts
testEndpoint('GET', 'blog-posts', [], 'Get All Blog Posts');

// Test 2: Get blog posts with search
testEndpoint('GET', 'blog-posts', ['search' => 'interview'], 'Search Blog Posts');

// Test 3: Get blog posts by category
testEndpoint('GET', 'blog-posts', ['category' => 'Career Development'], 'Get Posts by Category');

// Test 4: Get featured blog posts
testEndpoint('GET', 'blog-posts', ['featured' => 'true'], 'Get Featured Posts');

// Test 5: Get blog posts with pagination
testEndpoint('GET', 'blog-posts', ['per_page' => 5, 'page' => 1], 'Get Posts with Pagination');

// Test 6: Get single blog post
testEndpoint('GET', 'blog-posts/top-10-job-interview-tips-ugandan-job-seekers', [], 'Get Single Blog Post');

// Test 7: Get blog categories
testEndpoint('GET', 'blog-categories', [], 'Get Blog Categories');

// Test 8: Get blog tags
testEndpoint('GET', 'blog-tags', [], 'Get Blog Tags');

// Test 9: Record blog post view
testEndpoint('POST', 'blog-posts/1/view', [], 'Record Blog Post View');

// Test 10: Like blog post
testEndpoint('POST', 'blog-posts/1/like', [], 'Like Blog Post');

echo "\n" . str_repeat('=', 60) . "\n";
echo "🎉 Blog API Testing Completed!\n";
echo str_repeat('=', 60) . "\n";
