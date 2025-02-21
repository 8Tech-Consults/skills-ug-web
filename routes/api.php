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
Route::POST("send-mail-verification-code", [ApiAuthController::class, 'send_mail_verification_code']);


Route::get('api/{model}', [ApiResurceController::class, 'index']);
Route::post('api/{model}', [ApiResurceController::class, 'update']);

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
