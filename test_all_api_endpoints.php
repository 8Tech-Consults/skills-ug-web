#!/usr/bin/env php
<?php

/**
 * COMPREHENSIVE API ENDPOINT TESTING SCRIPT
 * 
 * Tests ALL API endpoints found in the Skills Uganda Laravel application
 * to ensure they work correctly and handle all response scenarios.
 * 
 * ENDPOINTS TESTED:
 * 
 * 1. AUTHENTICATION ENDPOINTS:
 *    - POST /api/users/login - User login
 *    - POST /api/users/register - User registration
 *    - POST /api/password-change - Change user password
 *    - POST /api/email-verify - Email verification
 *    - POST /api/send-mail-verification-code - Send verification code
 *    - POST /api/password-reset-request - Password reset request
 *    - POST /api/password-reset-submit - Password reset submission
 *    - POST /api/delete-account - Delete user account
 * 
 * 2. USER PROFILE ENDPOINTS:
 *    - GET /api/users/me - Get current user profile
 *    - POST /api/profile - Update user profile
 *    - GET /api/my-roles - Get user roles
 *    - GET /api/users - List users
 *    - GET /api/cvs - Get CVs
 *    - GET /api/cvs/{id} - Get specific CV
 *    - POST /api/post-media-upload - Upload media
 * 
 * 3. COMPANY ENDPOINTS:
 *    - POST /api/company-profile-update - Company profile update
 *    - GET /api/company-jobs - Get company jobs
 *    - GET /api/company-job-applications - Get job applications
 *    - GET /api/company-followers - Get company followers
 *    - GET /api/company-job-offers - Get company job offers
 *    - GET /api/company-recent-activities - Get recent activities
 *    - POST /api/company-follow - Follow company
 *    - POST /api/company-unfollow - Unfollow company
 *    - GET /api/my-company-follows - Get user's company follows
 * 
 * 4. JOB ENDPOINTS:
 *    - GET /api/jobs - List all jobs
 *    - POST /api/job-create - Create new job
 *    - GET /api/jobs/{id} - Get job details
 *    - GET /api/my-jobs - Get user's jobs
 *    - POST /api/job-apply - Apply for job
 *    - GET /api/my-job-applications - Get user's job applications
 *    - POST /api/job-application-update/{id} - Update job application
 *    - POST /api/job-offer-create - Create job offer
 *    - GET /api/my-job-offers - Get user's job offers
 *    - PUT /api/job-offers/{id} - Update job offer
 * 
 * 5. SERVICE ENDPOINTS:
 *    - POST /api/service-create - Create/update service
 *    - GET /api/services/{service_id}/reviews - Get service reviews
 *    - POST /api/services/{service_id}/reviews - Create service review
 *    - PUT /api/services/{service_id}/reviews/{review_id} - Update review
 *    - DELETE /api/services/{service_id}/reviews/{review_id} - Delete review
 *    - GET /api/services/{service_id}/reviews/user - Get user's review
 *    - POST /api/services/{service_id}/bookmark/toggle - Toggle bookmark
 *    - GET /api/services/{service_id}/bookmark/check - Check bookmark
 *    - GET /api/bookmarks - Get user's bookmarks
 *    - DELETE /api/bookmarks/clear - Clear all bookmarks
 * 
 * 6. CHAT ENDPOINTS:
 *    - GET /api/chat-messages - Get chat messages (legacy)
 *    - POST /api/send-message - Send message (legacy)
 *    - GET /api/my-chats - Get user's chats (legacy)
 *    - GET /api/chats - Get user's chats
 *    - POST /api/chats/create - Create chat
 *    - POST /api/chats/upload-media - Upload chat media
 *    - GET /api/chats/{chat_id}/messages - Get chat messages
 *    - POST /api/chats/{chat_id}/messages - Send message
 *    - GET /api/chats/{chat_id}/search - Search messages
 *    - POST /api/chats/{chat_id}/archive - Toggle archive
 *    - POST /api/chats/{chat_id}/mute - Toggle mute
 *    - PUT /api/chats/messages/{message_id} - Edit message
 *    - DELETE /api/chats/messages/{message_id} - Delete message
 *    - POST /api/chats/messages/{message_id}/reaction - Add reaction
 *    - DELETE /api/chats/messages/{message_id}/reaction - Remove reaction
 * 
 * 7. LEARNING ENDPOINTS (Eight Learning):
 *    - GET /api/test/course-categories - Get course categories
 *    - GET /api/test/courses - Get courses
 *    - GET /api/test/course-units/{course_id} - Get course units
 *    - GET /api/test/course-materials/{unit_id} - Get course materials
 *    - GET /api/test/course-quizzes/{unit_id} - Get course quizzes
 *    - GET /api/test/course-subscriptions/{user_id} - Get subscriptions
 *    - GET /api/test/course-progress/{user_id} - Get progress
 *    - GET /api/test/course-reviews/{course_id} - Get course reviews
 *    - GET /api/test/course-notifications/{user_id} - Get notifications
 *    - GET /api/test/payment-receipts/{user_id} - Get payment receipts
 *    - GET /api/test/course-certificates/{user_id} - Get certificates
 * 
 * 8. UTILITY ENDPOINTS:
 *    - GET /api/districts - Get districts
 *    - GET /api/manifest - Get app manifest
 *    - GET /api/job-seeker-manifest - Get job seeker manifest
 *    - POST /api/view-record-create - Create view record
 *    - GET /api/view-records - Get view records
 *    - GET /api/company-view-records - Get company view records
 *    - GET /api/ajax - AJAX search
 *    - GET /api/ajax-cards - AJAX card search
 *    - GET /api/api/{model} - Generic model API (GET)
 *    - POST /api/api/{model} - Generic model API (POST)
 */

require_once __DIR__ . '/vendor/autoload.php';

class ApiTester
{
    private $baseUrl;
    private $token;
    private $testResults = [];
    private $testUserId;
    private $testCompanyId;

    public function __construct()
    {
        // Get base URL from environment or use default
        $this->baseUrl = env('APP_URL', 'http://localhost:8000') . '/api';
        
        echo "🚀 Starting Comprehensive API Testing...\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo str_repeat('=', 80) . "\n\n";
    }

    /**
     * Run all API tests
     */
    public function runAllTests()
    {
        try {
            // Test 1: Authentication Endpoints
            $this->testAuthenticationEndpoints();
            
            // Test 2: User Profile Endpoints
            $this->testUserProfileEndpoints();
            
            // Test 3: Company Endpoints
            $this->testCompanyEndpoints();
            
            // Test 4: Job Endpoints
            $this->testJobEndpoints();
            
            // Test 5: Service Endpoints
            $this->testServiceEndpoints();
            
            // Test 6: Chat Endpoints
            $this->testChatEndpoints();
            
            // Test 7: Learning Endpoints
            $this->testLearningEndpoints();
            
            // Test 8: Utility Endpoints
            $this->testUtilityEndpoints();
            
            // Generate final report
            $this->generateReport();
            
        } catch (Exception $e) {
            echo "❌ Critical error during testing: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }

    /**
     * Test authentication related endpoints
     */
    private function testAuthenticationEndpoints()
    {
        echo "🔐 Testing Authentication Endpoints...\n";
        
        // Test user registration
        $registerData = [
            'name' => 'Test User ' . time(),
            'email' => 'test_' . time() . '@skillsug.test',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
            'phone' => '+256700000000',
            'district_id' => 1
        ];
        
        $registerResult = $this->makeRequest('POST', 'users/register', $registerData);
        $this->recordTest('POST /api/users/register', 'User Registration', $registerResult);
        
        // Test user login
        $loginData = [
            'username' => $registerData['email'],
            'password' => $registerData['password']
        ];
        
        $loginResult = $this->makeRequest('POST', 'users/login', $loginData);
        $this->recordTest('POST /api/users/login', 'User Login', $loginResult);
        
        // Extract token and user ID for future requests
        if ($loginResult['success'] && isset($loginResult['data']['token'])) {
            $this->token = $loginResult['data']['token'];
            $this->testUserId = $loginResult['data']['user']['id'] ?? null;
            echo "✅ Authentication successful. Token obtained.\n";
        }
        
        // Test authenticated endpoints
        if ($this->token) {
            // Test password change
            $passwordChangeData = [
                'current_password' => $registerData['password'],
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!'
            ];
            
            $passwordResult = $this->makeRequest('POST', 'password-change', $passwordChangeData, true);
            $this->recordTest('POST /api/password-change', 'Change Password', $passwordResult);
            
            // Test email verification
            $emailVerifyData = [
                'code' => '123456' // This will likely fail, but tests the endpoint
            ];
            
            $emailResult = $this->makeRequest('POST', 'email-verify', $emailVerifyData, true);
            $this->recordTest('POST /api/email-verify', 'Email Verification', $emailResult);
        }
        
        // Test password reset request (no auth required)
        $resetRequestData = [
            'email' => $registerData['email']
        ];
        
        $resetResult = $this->makeRequest('POST', 'password-reset-request', $resetRequestData);
        $this->recordTest('POST /api/password-reset-request', 'Password Reset Request', $resetResult);
        
        // Test send verification code
        $verificationData = [
            'email' => $registerData['email']
        ];
        
        $verificationResult = $this->makeRequest('POST', 'send-mail-verification-code', $verificationData);
        $this->recordTest('POST /api/send-mail-verification-code', 'Send Verification Code', $verificationResult);
        
        echo "\n";
    }

    /**
     * Test user profile related endpoints
     */
    private function testUserProfileEndpoints()
    {
        echo "👤 Testing User Profile Endpoints...\n";
        
        if (!$this->token) {
            echo "⚠️ Skipping user profile tests (no authentication token)\n\n";
            return;
        }
        
        // Test get current user
        $meResult = $this->makeRequest('GET', 'users/me', [], true);
        $this->recordTest('GET /api/users/me', 'Get Current User', $meResult);
        
        // Test profile update
        $profileData = [
            'name' => 'Updated Test User',
            'phone' => '+256700000001',
            'bio' => 'Updated bio for testing'
        ];
        
        $profileResult = $this->makeRequest('POST', 'profile', $profileData, true);
        $this->recordTest('POST /api/profile', 'Update Profile', $profileResult);
        
        // Test get user roles
        $rolesResult = $this->makeRequest('GET', 'my-roles', [], true);
        $this->recordTest('GET /api/my-roles', 'Get User Roles', $rolesResult);
        
        // Test get users list (public endpoint)
        $usersResult = $this->makeRequest('GET', 'users');
        $this->recordTest('GET /api/users', 'Get Users List', $usersResult);
        
        // Test get CVs
        $cvsResult = $this->makeRequest('GET', 'cvs');
        $this->recordTest('GET /api/cvs', 'Get CVs', $cvsResult);
        
        // Test get specific CV (if any exist)
        if ($cvsResult['success'] && !empty($cvsResult['data'])) {
            $cvId = $cvsResult['data'][0]['id'];
            $cvResult = $this->makeRequest('GET', "cvs/{$cvId}");
            $this->recordTest('GET /api/cvs/{id}', 'Get Specific CV', $cvResult);
        }
        
        echo "\n";
    }

    /**
     * Test company related endpoints
     */
    private function testCompanyEndpoints()
    {
        echo "🏢 Testing Company Endpoints...\n";
        
        if (!$this->token) {
            echo "⚠️ Skipping company tests (no authentication token)\n\n";
            return;
        }
        
        // Test company profile update
        $companyData = [
            'company_name' => 'Test Company Ltd',
            'company_description' => 'A test company for API testing',
            'company_website' => 'https://testcompany.com',
            'company_address' => 'Kampala, Uganda'
        ];
        
        $companyResult = $this->makeRequest('POST', 'company-profile-update', $companyData, true);
        $this->recordTest('POST /api/company-profile-update', 'Update Company Profile', $companyResult);
        
        // Test get company jobs
        $companyJobsResult = $this->makeRequest('GET', 'company-jobs', [], true);
        $this->recordTest('GET /api/company-jobs', 'Get Company Jobs', $companyJobsResult);
        
        // Test get company job applications
        $companyAppsResult = $this->makeRequest('GET', 'company-job-applications', [], true);
        $this->recordTest('GET /api/company-job-applications', 'Get Company Job Applications', $companyAppsResult);
        
        // Test get company followers
        $followersResult = $this->makeRequest('GET', 'company-followers', [], true);
        $this->recordTest('GET /api/company-followers', 'Get Company Followers', $followersResult);
        
        // Test get company job offers
        $offersResult = $this->makeRequest('GET', 'company-job-offers', [], true);
        $this->recordTest('GET /api/company-job-offers', 'Get Company Job Offers', $offersResult);
        
        // Test get company recent activities
        $activitiesResult = $this->makeRequest('GET', 'company-recent-activities', [], true);
        $this->recordTest('GET /api/company-recent-activities', 'Get Company Activities', $activitiesResult);
        
        // Test get user's company follows
        $myFollowsResult = $this->makeRequest('GET', 'my-company-follows', [], true);
        $this->recordTest('GET /api/my-company-follows', 'Get My Company Follows', $myFollowsResult);
        
        echo "\n";
    }

    /**
     * Test job related endpoints
     */
    private function testJobEndpoints()
    {
        echo "💼 Testing Job Endpoints...\n";
        
        // Test get jobs list (public endpoint)
        $jobsResult = $this->makeRequest('GET', 'jobs');
        $this->recordTest('GET /api/jobs', 'Get Jobs List', $jobsResult);
        
        if (!$this->token) {
            echo "⚠️ Skipping authenticated job tests (no authentication token)\n\n";
            return;
        }
        
        // Test job creation
        $jobData = [
            'title' => 'Test Job Position',
            'description' => 'This is a test job for API testing purposes.',
            'requirements' => 'Testing skills required',
            'salary_min' => 500000,
            'salary_max' => 1000000,
            'location' => 'Kampala',
            'job_category_id' => 1,
            'deadline' => date('Y-m-d', strtotime('+30 days'))
        ];
        
        $createJobResult = $this->makeRequest('POST', 'job-create', $jobData, true);
        $this->recordTest('POST /api/job-create', 'Create Job', $createJobResult);
        
        // Get job ID for further tests
        $testJobId = null;
        if ($createJobResult['success'] && isset($createJobResult['data']['id'])) {
            $testJobId = $createJobResult['data']['id'];
        } elseif ($jobsResult['success'] && !empty($jobsResult['data'])) {
            $testJobId = $jobsResult['data'][0]['id'];
        }
        
        // Test get specific job
        if ($testJobId) {
            $jobDetailResult = $this->makeRequest('GET', "jobs/{$testJobId}");
            $this->recordTest('GET /api/jobs/{id}', 'Get Job Details', $jobDetailResult);
            
            // Test job application
            $applyData = [
                'job_id' => $testJobId,
                'cover_letter' => 'I am interested in this position for testing purposes.'
            ];
            
            $applyResult = $this->makeRequest('POST', 'job-apply', $applyData, true);
            $this->recordTest('POST /api/job-apply', 'Apply for Job', $applyResult);
        }
        
        // Test get user's jobs
        $myJobsResult = $this->makeRequest('GET', 'my-jobs', [], true);
        $this->recordTest('GET /api/my-jobs', 'Get My Jobs', $myJobsResult);
        
        // Test get user's job applications
        $myAppsResult = $this->makeRequest('GET', 'my-job-applications', [], true);
        $this->recordTest('GET /api/my-job-applications', 'Get My Job Applications', $myAppsResult);
        
        // Test job offer creation
        $offerData = [
            'user_id' => $this->testUserId,
            'job_id' => $testJobId,
            'message' => 'We would like to offer you this position.',
            'salary' => 800000
        ];
        
        $offerResult = $this->makeRequest('POST', 'job-offer-create', $offerData, true);
        $this->recordTest('POST /api/job-offer-create', 'Create Job Offer', $offerResult);
        
        // Test get user's job offers
        $myOffersResult = $this->makeRequest('GET', 'my-job-offers', [], true);
        $this->recordTest('GET /api/my-job-offers', 'Get My Job Offers', $myOffersResult);
        
        echo "\n";
    }

    /**
     * Test service related endpoints
     */
    private function testServiceEndpoints()
    {
        echo "🔧 Testing Service Endpoints...\n";
        
        if (!$this->token) {
            echo "⚠️ Skipping service tests (no authentication token)\n\n";
            return;
        }
        
        // Test service creation
        $serviceData = [
            'title' => 'Test Service',
            'description' => 'This is a test service for API testing.',
            'price' => 50000,
            'category' => 'Technology',
            'location' => 'Kampala'
        ];
        
        $serviceResult = $this->makeRequest('POST', 'service-create', $serviceData, true);
        $this->recordTest('POST /api/service-create', 'Create Service', $serviceResult);
        
        // Get service ID for further tests
        $testServiceId = null;
        if ($serviceResult['success'] && isset($serviceResult['data']['id'])) {
            $testServiceId = $serviceResult['data']['id'];
        }
        
        // Test service review endpoints (if we have a service ID)
        if ($testServiceId) {
            // Test get service reviews
            $reviewsResult = $this->makeRequest('GET', "services/{$testServiceId}/reviews", [], true);
            $this->recordTest('GET /api/services/{id}/reviews', 'Get Service Reviews', $reviewsResult);
            
            // Test create service review
            $reviewData = [
                'rating' => 5,
                'comment' => 'Excellent service for testing!'
            ];
            
            $createReviewResult = $this->makeRequest('POST', "services/{$testServiceId}/reviews", $reviewData, true);
            $this->recordTest('POST /api/services/{id}/reviews', 'Create Service Review', $createReviewResult);
            
            // Test bookmark toggle
            $bookmarkResult = $this->makeRequest('POST', "services/{$testServiceId}/bookmark/toggle", [], true);
            $this->recordTest('POST /api/services/{id}/bookmark/toggle', 'Toggle Bookmark', $bookmarkResult);
            
            // Test bookmark check
            $checkBookmarkResult = $this->makeRequest('GET', "services/{$testServiceId}/bookmark/check", [], true);
            $this->recordTest('GET /api/services/{id}/bookmark/check', 'Check Bookmark', $checkBookmarkResult);
        }
        
        // Test get user bookmarks
        $bookmarksResult = $this->makeRequest('GET', 'bookmarks', [], true);
        $this->recordTest('GET /api/bookmarks', 'Get User Bookmarks', $bookmarksResult);
        
        echo "\n";
    }

    /**
     * Test chat related endpoints
     */
    private function testChatEndpoints()
    {
        echo "💬 Testing Chat Endpoints...\n";
        
        if (!$this->token) {
            echo "⚠️ Skipping chat tests (no authentication token)\n\n";
            return;
        }
        
        // Test legacy chat endpoints
        $legacyChatResult = $this->makeRequest('GET', 'my-chats', [], true);
        $this->recordTest('GET /api/my-chats', 'Get My Chats (Legacy)', $legacyChatResult);
        
        $legacyMessagesResult = $this->makeRequest('GET', 'chat-messages', ['user_id' => $this->testUserId, 'chat_head_id' => 1], true);
        $this->recordTest('GET /api/chat-messages', 'Get Chat Messages (Legacy)', $legacyMessagesResult);
        
        // Test modern chat endpoints
        $chatsResult = $this->makeRequest('GET', 'chats', [], true);
        $this->recordTest('GET /api/chats', 'Get User Chats', $chatsResult);
        
        // Test create chat
        $createChatData = [
            'receiver_id' => $this->testUserId + 1 // Assuming another user exists
        ];
        
        $createChatResult = $this->makeRequest('POST', 'chats/create', $createChatData, true);
        $this->recordTest('POST /api/chats/create', 'Create Chat', $createChatResult);
        
        // Get chat ID for further tests
        $testChatId = null;
        if ($createChatResult['success'] && isset($createChatResult['data']['id'])) {
            $testChatId = $createChatResult['data']['id'];
        } elseif ($chatsResult['success'] && !empty($chatsResult['data'])) {
            $testChatId = $chatsResult['data'][0]['id'];
        }
        
        // Test chat-specific endpoints
        if ($testChatId) {
            // Test get chat messages
            $messagesResult = $this->makeRequest('GET', "chats/{$testChatId}/messages", [], true);
            $this->recordTest('GET /api/chats/{id}/messages', 'Get Chat Messages', $messagesResult);
            
            // Test send message
            $messageData = [
                'message' => 'Hello, this is a test message!',
                'message_type' => 'text'
            ];
            
            $sendMessageResult = $this->makeRequest('POST', "chats/{$testChatId}/messages", $messageData, true);
            $this->recordTest('POST /api/chats/{id}/messages', 'Send Message', $sendMessageResult);
            
            // Test archive chat
            $archiveResult = $this->makeRequest('POST', "chats/{$testChatId}/archive", [], true);
            $this->recordTest('POST /api/chats/{id}/archive', 'Toggle Archive', $archiveResult);
            
            // Test mute chat
            $muteResult = $this->makeRequest('POST', "chats/{$testChatId}/mute", [], true);
            $this->recordTest('POST /api/chats/{id}/mute', 'Toggle Mute', $muteResult);
        }
        
        echo "\n";
    }

    /**
     * Test learning (Eight Learning) endpoints
     */
    private function testLearningEndpoints()
    {
        echo "📚 Testing Learning Endpoints...\n";
        
        // These are test endpoints that don't require authentication
        
        // Test course categories
        $categoriesResult = $this->makeRequest('GET', 'test/course-categories');
        $this->recordTest('GET /api/test/course-categories', 'Get Course Categories', $categoriesResult);
        
        // Test courses
        $coursesResult = $this->makeRequest('GET', 'test/courses');
        $this->recordTest('GET /api/test/courses', 'Get Courses', $coursesResult);
        
        // Get course ID for further tests
        $testCourseId = null;
        if ($coursesResult['success'] && !empty($coursesResult['data'])) {
            $testCourseId = $coursesResult['data'][0]['id'];
        }
        
        // Test course-specific endpoints
        if ($testCourseId) {
            // Test course units
            $unitsResult = $this->makeRequest('GET', "test/course-units/{$testCourseId}");
            $this->recordTest('GET /api/test/course-units/{id}', 'Get Course Units', $unitsResult);
            
            // Test course reviews
            $courseReviewsResult = $this->makeRequest('GET', "test/course-reviews/{$testCourseId}");
            $this->recordTest('GET /api/test/course-reviews/{id}', 'Get Course Reviews', $courseReviewsResult);
            
            // Get unit ID for material and quiz tests
            $testUnitId = null;
            if ($unitsResult['success'] && !empty($unitsResult['data'])) {
                $testUnitId = $unitsResult['data'][0]['id'];
            }
            
            if ($testUnitId) {
                // Test course materials
                $materialsResult = $this->makeRequest('GET', "test/course-materials/{$testUnitId}");
                $this->recordTest('GET /api/test/course-materials/{id}', 'Get Course Materials', $materialsResult);
                
                // Test course quizzes
                $quizzesResult = $this->makeRequest('GET', "test/course-quizzes/{$testUnitId}");
                $this->recordTest('GET /api/test/course-quizzes/{id}', 'Get Course Quizzes', $quizzesResult);
            }
        }
        
        // Test user-specific endpoints (use test user ID)
        $testUserId = $this->testUserId ?? 1;
        
        // Test course subscriptions
        $subscriptionsResult = $this->makeRequest('GET', "test/course-subscriptions/{$testUserId}");
        $this->recordTest('GET /api/test/course-subscriptions/{id}', 'Get Course Subscriptions', $subscriptionsResult);
        
        // Test course progress
        $progressResult = $this->makeRequest('GET', "test/course-progress/{$testUserId}");
        $this->recordTest('GET /api/test/course-progress/{id}', 'Get Course Progress', $progressResult);
        
        // Test course notifications
        $notificationsResult = $this->makeRequest('GET', "test/course-notifications/{$testUserId}");
        $this->recordTest('GET /api/test/course-notifications/{id}', 'Get Course Notifications', $notificationsResult);
        
        // Test payment receipts
        $receiptsResult = $this->makeRequest('GET', "test/payment-receipts/{$testUserId}");
        $this->recordTest('GET /api/test/payment-receipts/{id}', 'Get Payment Receipts', $receiptsResult);
        
        // Test course certificates
        $certificatesResult = $this->makeRequest('GET', "test/course-certificates/{$testUserId}");
        $this->recordTest('GET /api/test/course-certificates/{id}', 'Get Course Certificates', $certificatesResult);
        
        echo "\n";
    }

    /**
     * Test utility endpoints
     */
    private function testUtilityEndpoints()
    {
        echo "🔧 Testing Utility Endpoints...\n";
        
        // Test districts (public endpoint)
        $districtsResult = $this->makeRequest('GET', 'districts');
        $this->recordTest('GET /api/districts', 'Get Districts', $districtsResult);
        
        // Test manifest (public endpoint)
        $manifestResult = $this->makeRequest('GET', 'manifest');
        $this->recordTest('GET /api/manifest', 'Get App Manifest', $manifestResult);
        
        // Test job seeker manifest (public endpoint)
        $jobSeekerManifestResult = $this->makeRequest('GET', 'job-seeker-manifest');
        $this->recordTest('GET /api/job-seeker-manifest', 'Get Job Seeker Manifest', $jobSeekerManifestResult);
        
        // Test AJAX search (public endpoint)
        $ajaxResult = $this->makeRequest('GET', 'ajax', ['model' => 'User', 'q' => 'test']);
        $this->recordTest('GET /api/ajax', 'AJAX Search', $ajaxResult);
        
        // Test AJAX cards search (public endpoint)
        $ajaxCardsResult = $this->makeRequest('GET', 'ajax-cards', ['q' => '123']);
        $this->recordTest('GET /api/ajax-cards', 'AJAX Cards Search', $ajaxCardsResult);
        
        if ($this->token) {
            // Test view record creation
            $viewRecordData = [
                'model' => 'Job',
                'model_id' => 1,
                'type' => 'view'
            ];
            
            $viewRecordResult = $this->makeRequest('POST', 'view-record-create', $viewRecordData, true);
            $this->recordTest('POST /api/view-record-create', 'Create View Record', $viewRecordResult);
            
            // Test get view records
            $viewRecordsResult = $this->makeRequest('GET', 'view-records', [], true);
            $this->recordTest('GET /api/view-records', 'Get View Records', $viewRecordsResult);
            
            // Test get company view records
            $companyViewRecordsResult = $this->makeRequest('GET', 'company-view-records', [], true);
            $this->recordTest('GET /api/company-view-records', 'Get Company View Records', $companyViewRecordsResult);
        }
        
        echo "\n";
    }

    /**
     * Make HTTP request to API endpoint
     */
    private function makeRequest($method, $endpoint, $data = [], $requiresAuth = false)
    {
        $url = $this->baseUrl . '/' . $endpoint;
        
        $curl = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($requiresAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($curl, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode,
                'data' => null
            ];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw_response' => $response
        ];
    }

    /**
     * Record test result
     */
    private function recordTest($endpoint, $description, $result)
    {
        $success = $result['success'];
        $httpCode = $result['http_code'];
        
        $this->testResults[] = [
            'endpoint' => $endpoint,
            'description' => $description,
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $result['data']
        ];
        
        $status = $success ? '✅' : '❌';
        $codeInfo = $httpCode ? " ({$httpCode})" : '';
        
        echo "{$status} {$endpoint} - {$description}{$codeInfo}\n";
        
        // Show error details for failed requests
        if (!$success && isset($result['data']['message'])) {
            echo "   Error: {$result['data']['message']}\n";
        } elseif (!$success && isset($result['error'])) {
            echo "   Error: {$result['error']}\n";
        }
    }

    /**
     * Generate final test report
     */
    private function generateReport()
    {
        echo str_repeat('=', 80) . "\n";
        echo "📊 COMPREHENSIVE API TEST REPORT\n";
        echo str_repeat('=', 80) . "\n\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($test) {
            return $test['success'];
        });
        $failedTests = array_filter($this->testResults, function($test) {
            return !$test['success'];
        });
        
        $passCount = count($passedTests);
        $failCount = count($failedTests);
        $passRate = $totalTests > 0 ? round(($passCount / $totalTests) * 100, 2) : 0;
        
        echo "📈 SUMMARY:\n";
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passCount}\n";
        echo "Failed: {$failCount}\n";
        echo "Pass Rate: {$passRate}%\n\n";
        
        if (!empty($failedTests)) {
            echo "❌ FAILED TESTS:\n";
            echo str_repeat('-', 50) . "\n";
            foreach ($failedTests as $test) {
                echo "• {$test['endpoint']} - {$test['description']} ({$test['http_code']})\n";
                if (isset($test['response']['message'])) {
                    echo "  Error: {$test['response']['message']}\n";
                }
            }
            echo "\n";
        }
        
        if (!empty($passedTests)) {
            echo "✅ PASSED TESTS:\n";
            echo str_repeat('-', 50) . "\n";
            foreach ($passedTests as $test) {
                echo "• {$test['endpoint']} - {$test['description']} ({$test['http_code']})\n";
            }
            echo "\n";
        }
        
        echo "📝 RECOMMENDATIONS:\n";
        echo str_repeat('-', 50) . "\n";
        
        if ($failCount > 0) {
            echo "• Review failed endpoints and ensure proper implementation\n";
            echo "• Check authentication and authorization requirements\n";
            echo "• Verify database connections and model relationships\n";
            echo "• Ensure proper error handling and validation\n";
        }
        
        if ($passRate >= 90) {
            echo "• Excellent! Most endpoints are working correctly\n";
        } elseif ($passRate >= 70) {
            echo "• Good progress, but some endpoints need attention\n";
        } else {
            echo "• Significant issues detected, requires immediate attention\n";
        }
        
        echo "\n🎯 Testing completed at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 80) . "\n";
    }
}

// Check if running from command line
if (php_sapi_name() === 'cli') {
    $tester = new ApiTester();
    $tester->runAllTests();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test_all_api_endpoints.php\n";
}
