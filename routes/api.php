<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiResurceController;
use App\Http\Controllers\MainController;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware([EnsureTokenIsValid::class])->group(function () {});

Route::POST("profile", [ApiAuthController::class, "profile_update"]);
Route::POST("service-create", [ApiAuthController::class, "service_create"]);
Route::POST("company-profile-update", [ApiAuthController::class, "company_profile_update"]);
Route::POST("users/login", [ApiAuthController::class, "login"]);
Route::POST("users/register", [ApiAuthController::class, "register"]);
Route::POST("job-create", [ApiAuthController::class, "job_create"]);
Route::POST("job-offer-create", [ApiAuthController::class, "job_offer_create"]);
Route::POST("view-record-create", [ApiAuthController::class, "view_record_create"]);
Route::get("view-records", [ApiAuthController::class, "view_records"]);
Route::get("company-view-records", [ApiAuthController::class, "company_view_records"]);
Route::POST("job-apply", [ApiAuthController::class, "job_apply"]);
Route::POST("company-follow", [ApiAuthController::class, "company_follow"]);
Route::POST("company-unfollow", [ApiAuthController::class, "company_unfollow"]);
Route::get("company-followers", [ApiAuthController::class, "company_followers"]);
Route::POST('job-application-update/{id}', [ApiAuthController::class, 'job_application_update']);
Route::get("my-job-applications", [ApiAuthController::class, "my_job_applications"]);
Route::get("my-job-offers", [ApiAuthController::class, "my_job_offers"]);
Route::get("my-company-follows", [ApiAuthController::class, "my_company_follows"]);
Route::get("company-job-offers", [ApiAuthController::class, "company_job_offers"]);
Route::put('job-offers/{id}', [ApiAuthController::class, 'update_job_offer']);
Route::get("company-job-applications", [ApiAuthController::class, "company_job_applications"]);
Route::get("company-recent-activities", [ApiAuthController::class, "company_recent_activities"]);
Route::get('users/me', [ApiAuthController::class, 'me']);
Route::get('users', [ApiAuthController::class, 'users']);
Route::get('jobs', [ApiAuthController::class, 'jobs']);
Route::get('company-jobs', [ApiAuthController::class, 'company_jobs']);
Route::get('districts', [ApiAuthController::class, 'districts']);
Route::get('manifest', [ApiAuthController::class, 'manifest']);
Route::get('job-seeker-manifest', [ApiAuthController::class, 'job_seeker_manifest']);
Route::get('my-jobs', [ApiAuthController::class, 'my_jobs']);
Route::get('cvs', [ApiAuthController::class, 'cvs']);
Route::get('jobs/{id}', [MainController::class, 'job_single']);
Route::get('cvs/{id}', [MainController::class, 'cv_single']);
Route::POST("post-media-upload", [ApiAuthController::class, 'upload_media']);
Route::get('my-roles', [ApiAuthController::class, 'my_roles']);
Route::POST("delete-account", [ApiAuthController::class, 'delete_profile']);
Route::POST("password-change", [ApiAuthController::class, 'password_change']);
Route::POST("email-verify", [ApiAuthController::class, 'email_verify']);
Route::POST("send-mail-verification-code", [MainController::class, 'send_mail_verification_code']);
Route::POST("password-reset-request", [MainController::class, 'password_reset_request']);
Route::POST("password-reset-submit", [MainController::class, 'password_reset_submit']);

// Eight Learning Course API Routes
Route::get('course-categories', [ApiAuthController::class, 'course_categories']);
Route::get('courses', [ApiAuthController::class, 'courses']);
Route::get('courses/{id}', [ApiAuthController::class, 'course_single']);
Route::POST('course-subscribe', [ApiAuthController::class, 'course_subscribe']);
Route::POST('course-subscriptions/subscribe', [ApiAuthController::class, 'course_subscribe']);
Route::get('my-course-subscriptions', [ApiAuthController::class, 'my_course_subscriptions']);

// GDPR Compliance Routes
Route::prefix('gdpr')->group(function () {
    // Consent Management
    Route::get('consents', [\App\Http\Controllers\Api\GdprController::class, 'getConsents']);
    Route::post('consents', [\App\Http\Controllers\Api\GdprController::class, 'updateConsent']);
    
    // GDPR Requests
    Route::get('requests', [\App\Http\Controllers\Api\GdprController::class, 'getRequests']);
    Route::post('requests', [\App\Http\Controllers\Api\GdprController::class, 'createRequest']);
    Route::delete('requests/{requestId}', [\App\Http\Controllers\Api\GdprController::class, 'cancelRequest']);
    
    // Data Summary
    Route::get('data-summary', [\App\Http\Controllers\Api\GdprController::class, 'getDataSummary']);
});

// Enhanced Learning System API Routes
use App\Http\Controllers\Api\LearningController;
use App\Http\Controllers\Api\GdprController;

Route::middleware('auth:sanctum')->group(function () {
    // Learning Dashboard
    Route::get('learning/dashboard', [LearningController::class, 'getLearningDashboard']);
    
    // Course Learning
    Route::get('learning/courses/{courseId}', [LearningController::class, 'getCourseForLearning']);
    Route::get('learning/materials/{materialId}', [LearningController::class, 'getMaterialContent']);
    Route::post('learning/progress', [LearningController::class, 'updateMaterialProgress']);
    
    // Certificates
    Route::get('learning/certificates', [LearningController::class, 'getCertificates']);
    
    // Reviews
    Route::post('learning/reviews', [LearningController::class, 'submitCourseReview']);
    
    // Notifications
    Route::put('learning/notifications/{notificationId}/read', [LearningController::class, 'markNotificationAsRead']);
});


Route::get('api/{model}', [ApiResurceController::class, 'index']);
Route::post('api/{model}', [ApiResurceController::class, 'update']);

// Eight Learning Test Routes (No Auth Required)
Route::get('test/course-categories', function () {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseCategory::all()
    ]);
});

Route::get('test/courses', function () {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\Course::with('category')->get()
    ]);
});

Route::get('test/course-units/{course_id}', function ($course_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseUnit::where('course_id', $course_id)->get()
    ]);
});

Route::get('test/course-materials/{unit_id}', function ($unit_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseMaterial::where('unit_id', $unit_id)->get()
    ]);
});

Route::get('test/course-quizzes/{unit_id}', function ($unit_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseQuiz::where('unit_id', $unit_id)->get()
    ]);
});

Route::get('test/course-subscriptions/{user_id}', function ($user_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseSubscription::where('user_id', $user_id)->get()
    ]);
});

Route::get('test/course-progress/{user_id}', function ($user_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseProgress::where('user_id', $user_id)->get()
    ]);
});

// Course Progress Tracking API Routes
Route::middleware('auth:sanctum')->prefix('course-progress')->group(function () {
    // Track material progress
    Route::post('track-material', [App\Http\Controllers\Api\LearningController::class, 'trackMaterialProgress']);
    
    // Mark material as completed
    Route::post('complete-material', [App\Http\Controllers\Api\LearningController::class, 'markMaterialCompleted']);
    
    // Get material progress
    Route::get('material-progress', [App\Http\Controllers\Api\LearningController::class, 'getMaterialProgress']);
    
    // Get unit progress
    Route::get('unit-progress', [App\Http\Controllers\Api\LearningController::class, 'getUnitProgress']);
    
    // Get course progress
    Route::get('course-progress', [App\Http\Controllers\Api\LearningController::class, 'getCourseProgress']);
    
    // Update time tracking
    Route::post('update-time', [App\Http\Controllers\Api\LearningController::class, 'updateTimeTracking']);
    
    // Batch update progress
    Route::post('batch-update', [App\Http\Controllers\Api\LearningController::class, 'batchUpdateProgress']);
});

Route::get('test/course-reviews/{course_id}', function ($course_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseReview::where('course_id', $course_id)->get()
    ]);
});

Route::get('test/course-notifications/{user_id}', function ($user_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseNotification::where('user_id', $user_id)->get()
    ]);
});

Route::get('test/payment-receipts/{user_id}', function ($user_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\PaymentReceipt::where('user_id', $user_id)->get()
    ]);
});

Route::get('test/course-certificates/{user_id}', function ($user_id) {
    return response()->json([
        'code' => 1,
        'message' => 'Success',
        'data' => \App\Models\CourseCertificate::where('user_id', $user_id)->get()
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('ajax', function (Request $r) {

    $_model = trim($r->get('model'));
    $conditions = [];
    foreach ($_GET as $key => $v) {
        if (substr($key, 0, 6) != 'query_') {
            continue;
        }
        $_key = str_replace('query_', "", $key);
        $conditions[$_key] = $v;
    }

    if (strlen($_model) < 2) {
        return [
            'data' => []
        ];
    }

    $model = "App\Models\\" . $_model;
    $search_by_1 = trim($r->get('search_by_1'));
    $search_by_2 = trim($r->get('search_by_2'));

    // Validate search fields
    if (empty($search_by_1)) {
        return [
            'error' => 'search_by_1 parameter is required',
            'data' => []
        ];
    }

    $q = trim($r->get('q'));

    $res_1 = $model::where(
        $search_by_1,
        'like',
        "%$q%"
    )
        ->where($conditions)
        ->limit(20)->get();
    $res_2 = [];

    if ((count($res_1) < 20) && (strlen($search_by_2) > 1)) {
        $res_2 = $model::where(
            $search_by_2,
            'like',
            "%$q%"
        )
            ->where($conditions)
            ->limit(20)->get();
    }

    $data = [];
    foreach ($res_1 as $key => $v) {
        $name = "";
        if (isset($v->name)) {
            $name = " - " . $v->name;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id" . $name
        ];
    }
    foreach ($res_2 as $key => $v) {
        $name = "";
        if (isset($v->name)) {
            $name = " - " . $v->name;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id" . $name
        ];
    }

    return [
        'data' => $data
    ];
});

Route::get('ajax-cards', function (Request $r) {

    $users = User::where('card_number', 'like', "%" . $r->get('q') . "%")
        ->limit(20)->get();
    $data = [];
    foreach ($users as $key => $v) {
        if ($v->card_status != "Active") {
            continue;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id - $v->card_number"
        ];
    }
    return [
        'data' => $data
    ];


    $_model = trim($r->get('model'));
    $conditions = [];
    foreach ($_GET as $key => $v) {
        if (substr($key, 0, 6) != 'query_') {
            continue;
        }
        $_key = str_replace('query_', "", $key);
        $conditions[$_key] = $v;
    }

    if (strlen($_model) < 2) {
        return [
            'data' => []
        ];
    }

    $model = "App\Models\\" . $_model;
    $search_by_1 = trim($r->get('search_by_1'));
    $search_by_2 = trim($r->get('search_by_2'));

    $q = trim($r->get('q'));

    $res_1 = $model::where(
        $search_by_1,
        'like',
        "%$q%"
    )
        ->where($conditions)
        ->limit(20)->get();
    $res_2 = [];

    if ((count($res_1) < 20) && (strlen($search_by_2) > 1)) {
        $res_2 = $model::where(
            $search_by_2,
            'like',
            "%$q%"
        )
            ->where($conditions)
            ->limit(20)->get();
    }

    $data = [];
    foreach ($res_1 as $key => $v) {
        $name = "";
        if (isset($v->name)) {
            $name = " - " . $v->name;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id" . $name
        ];
    }
    foreach ($res_2 as $key => $v) {
        $name = "";
        if (isset($v->name)) {
            $name = " - " . $v->name;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id" . $name
        ];
    }

    return [
        'data' => $data
    ];
});

// Service Review Routes
use App\Http\Controllers\Api\ServiceReviewController;
use App\Http\Controllers\Api\ServiceBookmarkController;
use App\Http\Controllers\Api\ChatController;

Route::prefix('services/{service_id}/reviews')->group(function () {
    Route::get('/', [ServiceReviewController::class, 'index']);
    Route::post('/', [ServiceReviewController::class, 'store']);
    Route::put('/{review_id}', [ServiceReviewController::class, 'update']);
    Route::delete('/{review_id}', [ServiceReviewController::class, 'destroy']);
    Route::get('/user', [ServiceReviewController::class, 'getUserReview']);
});

// Service Bookmark Routes
Route::prefix('services/{service_id}/bookmark')->group(function () {
    Route::post('/toggle', [ServiceBookmarkController::class, 'toggle']);
    Route::get('/check', [ServiceBookmarkController::class, 'check']);
});

Route::prefix('bookmarks')->group(function () {
    Route::get('/', [ServiceBookmarkController::class, 'index']);
    Route::delete('/clear', [ServiceBookmarkController::class, 'clear']);
});

// Legacy Chat Routes (for mobile app compatibility)
Route::get('chat-messages', [ChatController::class, 'getChatMessages']);
Route::post('send-message', [ChatController::class, 'sendMessageLegacy']);
Route::get('my-chats', [ChatController::class, 'getMyChats']);

// Chat Routes
Route::prefix('chats')->group(function () {
    // Get user's chats
    Route::get('/', [ChatController::class, 'getChats']);

    // Get or create chat between users
    Route::post('/create', [ChatController::class, 'getOrCreateChat']);

    // Upload media for chat
    Route::post('/upload-media', [ChatController::class, 'uploadMedia']);

    // Chat-specific routes
    Route::prefix('{chat_id}')->group(function () {
        // Get messages
        Route::get('/messages', [ChatController::class, 'getMessages']);

        // Send message
        Route::post('/messages', [ChatController::class, 'sendMessage']);

        // Search messages
        Route::get('/search', [ChatController::class, 'searchMessages']);

        // Archive/unarchive chat
        Route::post('/archive', [ChatController::class, 'toggleArchive']);

        // Mute/unmute chat
        Route::post('/mute', [ChatController::class, 'toggleMute']);
    });

    // Message-specific routes
    Route::prefix('messages/{message_id}')->group(function () {
        // Edit message
        Route::put('/', [ChatController::class, 'editMessage']);

        // Delete message
        Route::delete('/', [ChatController::class, 'deleteMessage']);

        // Add reaction
        Route::post('/reaction', [ChatController::class, 'addReaction']);

        // Remove reaction
        Route::delete('/reaction', [ChatController::class, 'removeReaction']);
    });
});
