<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminRoleUser;
use App\Models\CompanyFollow;
use App\Models\Consultation;
use App\Models\District;
use App\Models\DoseItem;
use App\Models\DoseItemRecord;
use App\Models\FlutterWaveLog;
use App\Models\Image;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobOffer;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderItem;
use App\Models\LaundryOrderItemType;
use App\Models\Meeting;
use App\Models\PaymentRecord;
use App\Models\Project;
use App\Models\Service;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use App\Models\Utils;
use App\Models\ViewRecord;
use App\Traits\ApiResponser;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuthController extends Controller
{

    use ApiResponser;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {

        /* $token = auth('api')->attempt([
            'username' => 'admin',
            'password' => 'admin',
        ]);
        die($token); */
        $this->middleware('auth:api', ['except' => [
            'login',
            'register',
            'manifest',
            'jobs',
            'users',
            'jobs/*',
            'cvs/*',
            'password-reset-request',
            'password-reset-submit',
            'districts',
            'cvs',
            'send-mail-verification-code',
            'password-reset-submit',
        ]]);
    }
    /* 
Route::POST("", [ApiAuthController::class, 'send_mail_verification_code']);
Route::POST("", [ApiAuthController::class, 'password_reset_request']);
Route::POST("", [ApiAuthController::class, 'password_reset_submit']);

*/

    /**
     * @OA\Post(
     *     path="/job-apply",
     *     summary="Apply for a job",
     *     description="Submits a job application for the authenticated user. The user must not have already applied for the job.",
     *     operationId="jobApply",
     *     tags={"Job"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job application details",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"job_id"},
     *             @OA\Property(
     *                 property="job_id",
     *                 type="integer",
     *                 example=10,
     *                 description="The ID of the job the user is applying for."
     *             ),
     *             @OA\Property(
     *                 property="cover_letter",
     *                 type="string",
     *                 example="I am very interested in this position and believe I am a great fit.",
     *                 description="Optional cover letter provided by the applicant."
     *             ),
     *             @OA\Property(
     *                 property="resume_url",
     *                 type="string",
     *                 example="https://example.com/resume.pdf",
     *                 description="Optional URL pointing to the applicant's resume."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job Application submitted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job Application submitted successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=100),
     *                 @OA\Property(property="job_id", type="integer", example=10),
     *                 @OA\Property(property="applicant_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input or account not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Duplicate application",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You have already applied for this job.")
     *         )
     *     )
     * )
     */
    public function job_apply(Request $r)
    {
        $user = auth('api')->user();
        if ($user == null) {
            return $this->error('Account not found.');
        }
        $jobAppication = JobApplication::where([
            'applicant_id' => $user->id,
            'job_id' => $r->job_id,
        ])->first();
        if ($jobAppication != null) {
            return $this->error('You have already applied for this job.');
        }
        $jobAppication = new JobApplication();


        $except = ['id', 'applicant_id', 'status', 'slug', 'attachments'];
        try {
            $jobAppication = Utils::fetch_post($jobAppication, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        // return $this->error('Applicant not found.', $jobAppication);
        $attachments = [];
        /* if (!empty($_FILES['attachments'])) {
            $attachments = Utils::upload_files($_FILES['attachments'], 'attachments');
        } */
        $jobAppication->attachments = json_encode($attachments);
        $jobAppication->applicant_id = $user->id;
        try {
            $jobAppication->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $jobAppication = JobApplication::find($jobAppication->id);
        if ($jobAppication == null) {
            return $this->error('jobAppication not found.');
        }
        return $this->success($jobAppication, 'Job Application submitted successfully.');
    }

    //Route::POST("job-apply", [ApiAuthController::class, "job_apply"]);


    /**
     * @OA\Put(
     *     path="/job-application-update/{id}",
     *     summary="Update a job application",
     *     description="Updates the details of an existing job application.",
     *     operationId="updateJobApplication",
     *     tags={"Job Application"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the job application to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job application update details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="cover_letter",
     *                 type="string",
     *                 example="Updated cover letter",
     *                 description="Updated cover letter provided by the applicant."
     *             ),
     *             @OA\Property(
     *                 property="resume_url",
     *                 type="string",
     *                 example="https://example.com/updated_resume.pdf",
     *                 description="Updated URL pointing to the applicant's resume."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job Application updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job Application updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="job_id", type="integer", example=10),
     *                 @OA\Property(property="applicant_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid input.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Job Application not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job Application not found.")
     *         )
     *     )
     * )
     */
    public function job_application_update(Request $r)
    {
        $jobAppication = JobApplication::find($r->id);
        if ($jobAppication == null) {
            return $this->error('Job Application not found.');
        }

        $except = [
            'id',
            'applicant_id',
            'slug',
            'attachments',
        ];
        try {
            $jobAppication = Utils::fetch_post($jobAppication, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }


        try {
            $jobAppication->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $jobAppication = JobApplication::find($jobAppication->id);
        if ($jobAppication == null) {
            return $this->error('jobAppication not found.');
        }
        return $this->success($jobAppication, 'Job Application UPDATED successfully.');
    }



    /**
     * @OA\Post(
     *     path="/job-create",
     *     summary="Create or update a job posting",
     *     description="Creates a new job posting or updates an existing one if an 'id' is provided. The authenticated user is automatically set as the poster.",
     *     operationId="jobCreate",
     *     tags={"Job"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job posting details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="id",
     *                 type="integer",
     *                 example=1,
     *                 description="Job ID for updating an existing job. Omit or set to 0 for new job creation."
     *             ),
     *             @OA\Property(
     *                 property="created_at",
     *                 type="string",
     *                 format="date-time",
     *                 example="2023-01-01T00:00:00Z",
     *                 description="Job creation date (usually ignored on creation)"
     *             ),
     *             @OA\Property(
     *                 property="updated_at",
     *                 type="string",
     *                 format="date-time",
     *                 example="2023-01-02T00:00:00Z",
     *                 description="Job update date (usually ignored on creation)"
     *             ),
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Software Engineer",
     *                 description="Job title"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="active",
     *                 description="Job status"
     *             ),
     *             @OA\Property(
     *                 property="deadline",
     *                 type="string",
     *                 format="date",
     *                 example="2023-12-31",
     *                 description="Application deadline"
     *             ),
     *             @OA\Property(
     *                 property="category_id",
     *                 type="integer",
     *                 example=5,
     *                 description="Job category ID"
     *             ),
     *             @OA\Property(
     *                 property="district_id",
     *                 type="integer",
     *                 example=10,
     *                 description="District ID"
     *             ),
     *             @OA\Property(
     *                 property="sub_county_id",
     *                 type="integer",
     *                 example=2,
     *                 description="Sub-county ID"
     *             ),
     *             @OA\Property(
     *                 property="address",
     *                 type="string",
     *                 example="123 Main St, City",
     *                 description="Job address"
     *             ),
     *             @OA\Property(
     *                 property="vacancies_count",
     *                 type="integer",
     *                 example=3,
     *                 description="Number of vacancies"
     *             ),
     *             @OA\Property(
     *                 property="employment_status",
     *                 type="string",
     *                 example="Full Time",
     *                 description="Employment status (e.g., Full Time, Part Time, Contract, Internship)"
     *             ),
     *             @OA\Property(
     *                 property="workplace",
     *                 type="string",
     *                 example="Onsite",
     *                 description="Workplace type (Onsite or Remote)"
     *             ),
     *             @OA\Property(
     *                 property="responsibilities",
     *                 type="string",
     *                 example="Develop and maintain software applications",
     *                 description="Key responsibilities"
     *             ),
     *             @OA\Property(
     *                 property="experience_field",
     *                 type="string",
     *                 example="Software Development",
     *                 description="Relevant experience field"
     *             ),
     *             @OA\Property(
     *                 property="experience_period",
     *                 type="string",
     *                 example="2-3 years",
     *                 description="Experience duration"
     *             ),
     *             @OA\Property(
     *                 property="show_salary",
     *                 type="boolean",
     *                 example=true,
     *                 description="Whether to display salary information"
     *             ),
     *             @OA\Property(
     *                 property="minimum_salary",
     *                 type="number",
     *                 format="float",
     *                 example=50000,
     *                 description="Minimum salary range"
     *             ),
     *             @OA\Property(
     *                 property="maximum_salary",
     *                 type="number",
     *                 format="float",
     *                 example=70000,
     *                 description="Maximum salary range"
     *             ),
     *             @OA\Property(
     *                 property="benefits",
     *                 type="string",
     *                 example="Health insurance, Paid time off",
     *                 description="Job benefits"
     *             ),
     *             @OA\Property(
     *                 property="job_icon",
     *                 type="string",
     *                 example="/images/job_icon.png",
     *                 description="Icon or image path for the job"
     *             ),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 example="Any",
     *                 description="Gender requirement"
     *             ),
     *             @OA\Property(
     *                 property="min_age",
     *                 type="integer",
     *                 example=21,
     *                 description="Minimum age requirement"
     *             ),
     *             @OA\Property(
     *                 property="max_age",
     *                 type="integer",
     *                 example=60,
     *                 description="Maximum age requirement"
     *             ),
     *             @OA\Property(
     *                 property="required_video_cv",
     *                 type="boolean",
     *                 example=false,
     *                 description="Whether a video CV is required"
     *             ),
     *             @OA\Property(
     *                 property="minimum_academic_qualification",
     *                 type="string",
     *                 example="Bachelor's Degree",
     *                 description="Academic requirement"
     *             ),
     *             @OA\Property(
     *                 property="application_method",
     *                 type="string",
     *                 example="Email",
     *                 description="Method of application"
     *             ),
     *             @OA\Property(
     *                 property="application_method_details",
     *                 type="string",
     *                 example="Send your resume to hr@example.com",
     *                 description="Additional details on how to apply"
     *             ),
     *             @OA\Property(
     *                 property="slug",
     *                 type="string",
     *                 example="software-engineer",
     *                 description="SEO-friendly URL slug"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job created or updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Software Engineer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job not found.")
     *         )
     *     )
     * )
     */
    public function job_create(Request $r)
    {
        $user = auth('api')->user();
        if ($user == null) {
            return $this->error('Account not found.');
        }
        $id = (int)($r->id);
        $job = Job::find($id);
        $isCreating = false;
        if ($job == null) {
            $job = new Job();
            $isCreating = true;
            //post_by_id
            $job->posted_by_id = $user->id;
        }


        $except = ['id', 'posted_by_id', 'status', 'slug'];
        try {
            $job = Utils::fetch_post($job, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }


        try {
            $job->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $job = Job::find($job->id);
        if ($job == null) {
            return $this->error('Iob not found.');
        }
        $message = ($isCreating) ? 'Job created successfully.' : 'Job updated successfully.';
        return $this->success($job, $message);
    }



    /**
     * @OA\Post(
     *     path="/company-follow",
     *     summary="Follow a company",
     *     description="Allows an authenticated user to follow a company.",
     *     operationId="companyFollow",
     *     tags={"Company"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Company follow request data",
     *         @OA\JsonContent(
     *             required={"company_id"},
     *             @OA\Property(
     *                 property="company_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the company to follow"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company followed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Company followed successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="company_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Candidate not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Already following the company",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You have already followed this company. Go to your dashboard to unfollow.")
     *         )
     *     )
     * )
     */
    public function company_follow(Request $r)
    {
        $user = auth('api')->user();
        if ($user == null) {
            return $this->error('Account not found.');
        }
        $company = User::find($r->company_id);
        if ($company == null) {
            return $this->error('Candidate not found.');
        }
        //check if not already followuing
        $follow = CompanyFollow::where([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ])->first();
        if ($follow != null) {
            return $this->error('You have already followed this company. Go to your dashboard to unfollow.');
        }
        $follow = new CompanyFollow();
        $follow->user_id = $user->id;
        $follow->company_id = $company->id;

        try {
            $follow->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $follow = CompanyFollow::find($follow->id);
        if ($follow == null) {
            return $this->error('Follow not found.');
        }
        return $this->success($follow, 'Company followed successfully.');
    }


    /**
     * @OA\Post(
     *     path="/company-unfollow",
     *     summary="Unfollow a company",
     *     description="Allows an authenticated user to unfollow a company.",
     *     operationId="companyUnfollow",
     *     tags={"Company"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Company unfollow request data",
     *         @OA\JsonContent(
     *             required={"company_id"},
     *             @OA\Property(
     *                 property="company_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the company to unfollow"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company unfollowed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Company unfollowed successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="company_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid input.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Follow record not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Follow record not found.")
     *         )
     *     )
     * )
     */
    public function company_unfollow(Request $r)
    {
        $user = auth('api')->user();
        if ($user == null) {
            return $this->error('Account not found.');
        }
        $company = User::find($r->company_id);
        if ($company == null) {
            return $this->error('Company not found.');
        }
        $follow = CompanyFollow::where([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ])->first();
        if ($follow == null) {
            return $this->error('You are not following this company.');
        }
        try {
            $follow->delete();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
        return $this->success(null, 'Company unfollowed successfully.');
    }


    /**
     * @OA\Get(
     *     path="/company-followers",
     *     summary="Get followers of the authenticated company",
     *     description="Retrieves a list of users who are following the currently authenticated company.",
     *     operationId="getCompanyFollowers",
     *     tags={"Company"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Followers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Followers retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="company_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving followers.")
     *         )
     *     )
     * )
     */
    public function company_followers(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = CompanyFollow::where('company_id', $user->id);

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 100);
        $followers = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($followers, 'Followers retrieved successfully.');
    }


    /**
     * @OA\Post(
     *     path="/job-offer-create",
     *     summary="Create a job offer",
     *     description="Creates a new job offer for a candidate by the authenticated company.",
     *     operationId="createJobOffer",
     *     tags={"Job Offer"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job offer details",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"candidate_id", "job_title", "company_name", "salary", "start_date", "job_description"},
     *             @OA\Property(
     *                 property="candidate_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the candidate to whom the job offer is made"
     *             ),
     *             @OA\Property(
     *                 property="job_title",
     *                 type="string",
     *                 example="Software Engineer",
     *                 description="Title of the job being offered"
     *             ),
     *             @OA\Property(
     *                 property="company_name",
     *                 type="string",
     *                 example="Example Corp",
     *                 description="Name of the company making the offer"
     *             ),
     *             @OA\Property(
     *                 property="salary",
     *                 type="number",
     *                 format="float",
     *                 example=70000,
     *                 description="Salary offered for the job"
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2023-01-01",
     *                 description="Start date for the job"
     *             ),
     *             @OA\Property(
     *                 property="job_description",
     *                 type="string",
     *                 example="Develop and maintain software applications",
     *                 description="Description of the job responsibilities"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offer created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job offer created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="job_title", type="string", example="Software Engineer"),
     *                 @OA\Property(property="company_name", type="string", example="Example Corp"),
     *                 @OA\Property(property="salary", type="number", format="float", example=70000),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *                 @OA\Property(property="job_description", type="string", example="Develop and maintain software applications"),
     *                 @OA\Property(property="candidate_id", type="integer", example=1),
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="Pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid input.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Duplicate job offer",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You have already made an offer to this candidate and it is still pending.")
     *         )
     *     )
     * )
     */
    public function job_offer_create(Request $r)
    {
        $user = auth('api')->user();
        if ($user == null) {
            return $this->error('Account not found.');
        }
        $candidate = User::find($r->candidate_id);
        if ($candidate == null) {
            return $this->error('Candidate not found.');
        }

        //check if the company has already made an offer to the candidate and still pending
        $jobOffer = JobOffer::where([
            'company_id' => $user->id,
            'candidate_id' => $candidate->id,
            'status' => 'Pending',
        ])->first();
        if ($jobOffer != null) {
            return $this->error('You have already made an offer to this candidate and it is still pending.');
        }

        $id = (int)($r->id);
        $job = JobOffer::find($id);
        $isCreating = false;
        if ($job == null) {
            $job = new JobOffer();
            $isCreating = true;
            $job->company_id =  $user->id;
            $job->candidate_id =   $candidate->id;
        }

        $except = ['id', 'candidate_id', 'company_id', 'slug'];
        try {
            $job = Utils::fetch_post($job, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        /* 
        id	created_at	updated_at	job_title	company_name	salary	start_date	job_description	candidate_id	company_id	status	candidate_message	
        */



        try {
            $job->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $job = JobOffer::find($job->id);
        if ($job == null) {
            return $this->error('Job offer not found.');
        }
        $message = ($isCreating) ? 'Job offer created successfully.' : 'Job offer updated successfully.';
        return $this->success($job, $message);
    }


    /**
     * @OA\Post(
     *     path="/profile",
     *     summary="Update user profile",
     *     description="Updates the authenticated user's profile details. Accepts a wide range of user fields. Uploaded images (if any) are used to update the avatar.",
     *     operationId="updateUserProfile",
     *     tags={"User"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User profile data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1, description="Descending ID"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="remember_token", type="string", example="token123"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T00:00:00Z"),
     *             @OA\Property(property="enterprise_id", type="integer", example=101),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="place_of_birth", type="string", example="New York"),
     *             @OA\Property(property="sex", type="string", example="male"),
     *             @OA\Property(property="home_address", type="string", example="123 Main St"),
     *             @OA\Property(property="current_address", type="string", example="456 Secondary St"),
     *             @OA\Property(property="phone_number_1", type="string", example="+1234567890"),
     *             @OA\Property(property="phone_number_2", type="string", example="+0987654321"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="nationality", type="string", example="American"),
     *             @OA\Property(property="religion", type="string", example="Christian"),
     *             @OA\Property(property="spouse_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="spouse_phone", type="string", example="+1122334455"),
     *             @OA\Property(property="father_name", type="string", example="Robert Doe"),
     *             @OA\Property(property="father_phone", type="string", example="+1231231234"),
     *             @OA\Property(property="mother_name", type="string", example="Mary Doe"),
     *             @OA\Property(property="mother_phone", type="string", example="+3213214321"),
     *             @OA\Property(property="languages", type="string", example="English, Spanish"),
     *             @OA\Property(property="emergency_person_name", type="string", example="Alice Doe"),
     *             @OA\Property(property="emergency_person_phone", type="string", example="+14445556666"),
     *             @OA\Property(property="national_id_number", type="string", example="ID123456789"),
     *             @OA\Property(property="passport_number", type="string", example="P123456789"),
     *             @OA\Property(property="tin", type="string", example="TIN987654321"),
     *             @OA\Property(property="nssf_number", type="string", example="NSSF123456"),
     *             @OA\Property(property="bank_name", type="string", example="Bank of America"),
     *             @OA\Property(property="bank_account_number", type="string", example="123456789012"),
     *             @OA\Property(property="primary_school_name", type="string", example="Greenwood Primary School"),
     *             @OA\Property(property="primary_school_year_graduated", type="integer", example=2000),
     *             @OA\Property(property="seconday_school_name", type="string", example="Central High School"),
     *             @OA\Property(property="seconday_school_year_graduated", type="integer", example=2004),
     *             @OA\Property(property="high_school_name", type="string", example="City High School"),
     *             @OA\Property(property="high_school_year_graduated", type="integer", example=2008),
     *             @OA\Property(property="degree_university_name", type="string", example="State University"),
     *             @OA\Property(property="degree_university_year_graduated", type="integer", example=2012),
     *             @OA\Property(property="masters_university_name", type="string", example="State University"),
     *             @OA\Property(property="masters_university_year_graduated", type="integer", example=2014),
     *             @OA\Property(property="phd_university_name", type="string", example="State University"),
     *             @OA\Property(property="phd_university_year_graduated", type="integer", example=2018),
     *             @OA\Property(property="user_type", type="string", example="admin"),
     *             @OA\Property(property="demo_id", type="integer", example=10),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="user_batch_importer_id", type="integer", example=5),
     *             @OA\Property(property="school_pay_account_id", type="integer", example=200),
     *             @OA\Property(property="school_pay_payment_code", type="string", example="PAY123456"),
     *             @OA\Property(property="given_name", type="string", example="Johnny"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time", example="2023-01-03T00:00:00Z", nullable=true),
     *             @OA\Property(property="marital_status", type="string", example="single"),
     *             @OA\Property(property="verification", type="string", example="verified"),
     *             @OA\Property(property="current_class_id", type="integer", example=3),
     *             @OA\Property(property="current_theology_class_id", type="integer", example=2),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="parent_id", type="integer", example=0),
     *             @OA\Property(property="main_role_id", type="integer", example=1),
     *             @OA\Property(property="stream_id", type="integer", example=1),
     *             @OA\Property(property="account_id", type="integer", example=1001),
     *             @OA\Property(property="has_personal_info", type="boolean", example=true),
     *             @OA\Property(property="has_educational_info", type="boolean", example=true),
     *             @OA\Property(property="has_account_info", type="boolean", example=true),
     *             @OA\Property(property="diploma_school_name", type="string", example="Diploma Institute"),
     *             @OA\Property(property="diploma_year_graduated", type="integer", example=2010),
     *             @OA\Property(property="certificate_school_name", type="string", example="Certificate School"),
     *             @OA\Property(property="certificate_year_graduated", type="integer", example=2011),
     *             @OA\Property(property="company_id", type="integer", example=500),
     *             @OA\Property(property="managed_by", type="integer", example=10),
     *             @OA\Property(property="title", type="string", example="Mr."),
     *             @OA\Property(property="dob", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="intro", type="string", example="Hello, I am John."),
     *             @OA\Property(property="rate", type="number", format="float", example=4.5),
     *             @OA\Property(property="can_evaluate", type="boolean", example=true),
     *             @OA\Property(property="work_load_pending", type="number", example=2),
     *             @OA\Property(property="work_load_completed", type="number", example=5),
     *             @OA\Property(property="belongs_to_company", type="boolean", example=true),
     *             @OA\Property(property="card_status", type="string", example="active"),
     *             @OA\Property(property="card_number", type="string", example="CARD123456"),
     *             @OA\Property(property="card_balance", type="number", format="float", example=100.50),
     *             @OA\Property(property="card_accepts_credit", type="boolean", example=true),
     *             @OA\Property(property="card_max_credit", type="number", format="float", example=5000.00),
     *             @OA\Property(property="card_accepts_cash", type="boolean", example=false),
     *             @OA\Property(property="is_dependent", type="boolean", example=false),
     *             @OA\Property(property="dependent_status", type="string", example="none"),
     *             @OA\Property(property="dependent_id", type="integer", example=0),
     *             @OA\Property(property="card_expiry", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="belongs_to_company_status", type="string", example="verified"),
     *             @OA\Property(property="objective", type="string", example="To excel in my career"),
     *             @OA\Property(property="special_qualification", type="string", example="Certified Expert"),
     *             @OA\Property(property="career_summary", type="string", example="Over 10 years of experience in IT"),
     *             @OA\Property(property="present_salary", type="number", format="float", example=5000.00),
     *             @OA\Property(property="expected_salary", type="number", format="float", example=6000.00),
     *             @OA\Property(property="expected_job_level", type="string", example="Senior"),
     *             @OA\Property(property="expected_job_nature", type="string", example="Full-time"),
     *             @OA\Property(property="preferred_job_location", type="string", example="Remote"),
     *             @OA\Property(property="preferred_job_category", type="string", example="Technology"),
     *             @OA\Property(property="preferred_job_category_other", type="string", example="Software Development"),
     *             @OA\Property(property="preferred_job_districts", type="string", example="District 1"),
     *             @OA\Property(property="preferred_job_abroad", type="boolean", example=false),
     *             @OA\Property(property="preferred_job_countries", type="string", example="USA, Canada"),
     *             @OA\Property(property="has_disability", type="boolean", example=false),
     *             @OA\Property(property="is_registered_on_disability", type="boolean", example=false),
     *             @OA\Property(property="disability_type", type="string", example="N/A"),
     *             @OA\Property(property="dificulty_to_see", type="boolean", example=false),
     *             @OA\Property(property="dificulty_to_hear", type="boolean", example=false),
     *             @OA\Property(property="dificulty_to_walk", type="boolean", example=false),
     *             @OA\Property(property="dificulty_to_speak", type="boolean", example=false),
     *             @OA\Property(property="dificulty_display_on_cv", type="boolean", example=false),
     *             @OA\Property(property="country_code", type="string", example="+1"),
     *             @OA\Property(property="blood_group", type="string", example="O+"),
     *             @OA\Property(property="height", type="number", format="float", example=175.5),
     *             @OA\Property(property="weight", type="number", format="float", example=70.0),
     *             @OA\Property(property="company_name", type="string", example="Example Corp"),
     *             @OA\Property(property="company_year_of_establishment", type="integer", example=2000),
     *             @OA\Property(property="company_employees_range", type="string", example="50-200"),
     *             @OA\Property(property="company_country", type="string", example="USA"),
     *             @OA\Property(property="company_address", type="string", example="123 Corporate Blvd"),
     *             @OA\Property(property="company_district_id", type="integer", example=10),
     *             @OA\Property(property="company_sub_county_id", type="integer", example=20),
     *             @OA\Property(property="company_main_category_id", type="integer", example=5),
     *             @OA\Property(property="company_sub_category_id", type="integer", example=3),
     *             @OA\Property(property="company_phone_number", type="string", example="+123456789"),
     *             @OA\Property(property="company_description", type="string", example="Leading provider of example solutions."),
     *             @OA\Property(property="company_trade_license_no", type="string", example="TLN123456"),
     *             @OA\Property(property="company_website_url", type="string", example="https://example.com"),
     *             @OA\Property(property="company__email", type="string", format="email", example="info@example.com"),
     *             @OA\Property(property="company__phone", type="string", example="+123456789"),
     *             @OA\Property(property="company_has_accessibility", type="boolean", example=true),
     *             @OA\Property(property="company_has_disability_inclusion_policy", type="boolean", example=true),
     *             @OA\Property(property="company_logo", type="string", example="https://example.com/logo.png"),
     *             @OA\Property(property="company_tax_id", type="string", example="TAX123456"),
     *             @OA\Property(property="company_facebook_url", type="string", example="https://facebook.com/example"),
     *             @OA\Property(property="company_linkedin_url", type="string", example="https://linkedin.com/company/example"),
     *             @OA\Property(property="company_operating_hours", type="string", example="9AM-5PM"),
     *             @OA\Property(property="company_certifications", type="string", example="ISO9001"),
     *             @OA\Property(property="company_ownership_type", type="string", example="Private"),
     *             @OA\Property(property="company_status", type="string", example="active"),
     *             @OA\Property(property="is_company", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Profile updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="username", type="string", example="johndoe"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="avatar", type="string", example="images/avatar.png")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the profile.")
     *         )
     *     )
     * )
     */

    public function profile_update(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found.');
        }
        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('User Account not found.');
        }
        $except = ['password', 'password_confirmation', 'avatar',];
        try {
            $u = Utils::fetch_post($u, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }


        $images = [];
        if (!empty($_FILES)) {
            $images = Utils::upload_images_2($_FILES, false);
        }
        if (!empty($images)) {
            $u->avatar = 'images/' . $images[0];
        }

        try {
            $u->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('Account not found.');
        }
        return $this->success($u,  "Profile updated successfully.");
    }


    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Logout user",
     *     description="Logs out the authenticated user by invalidating the JWT token.",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Logged out successfully."),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        auth('api')->logout();
        return $this->success(null, 'Logged out successfully.');
    }



    /**
     * @OA\Post(
     *     path="/company-profile-update",
     *     summary="Update company profile",
     *     description="Updates the authenticated company's profile details. Accepts a wide range of company fields. Uploaded images (if any) are used to update the company logo.",
     *     operationId="updateCompanyProfile",
     *     tags={"Company"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Company profile data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="company_name", type="string", example="Example Corp"),
     *             @OA\Property(property="company_year_of_establishment", type="integer", example=2000),
     *             @OA\Property(property="company_employees_range", type="string", example="50-200"),
     *             @OA\Property(property="company_country", type="string", example="USA"),
     *             @OA\Property(property="company_address", type="string", example="123 Corporate Blvd"),
     *             @OA\Property(property="company_district_id", type="integer", example=10),
     *             @OA\Property(property="company_sub_county_id", type="integer", example=20),
     *             @OA\Property(property="company_main_category_id", type="integer", example=5),
     *             @OA\Property(property="company_sub_category_id", type="integer", example=3),
     *             @OA\Property(property="company_phone_number", type="string", example="+123456789"),
     *             @OA\Property(property="company_description", type="string", example="Leading provider of example solutions."),
     *             @OA\Property(property="company_trade_license_no", type="string", example="TLN123456"),
     *             @OA\Property(property="company_website_url", type="string", example="https://example.com"),
     *             @OA\Property(property="company_email", type="string", format="email", example="info@example.com"),
     *             @OA\Property(property="company_phone", type="string", example="+123456789"),
     *             @OA\Property(property="company_has_accessibility", type="boolean", example=true),
     *             @OA\Property(property="company_has_disability_inclusion_policy", type="boolean", example=true),
     *             @OA\Property(property="company_logo", type="string", example="https://example.com/logo.png"),
     *             @OA\Property(property="company_tax_id", type="string", example="TAX123456"),
     *             @OA\Property(property="company_facebook_url", type="string", example="https://facebook.com/example"),
     *             @OA\Property(property="company_linkedin_url", type="string", example="https://linkedin.com/company/example"),
     *             @OA\Property(property="company_operating_hours", type="string", example="9AM-5PM"),
     *             @OA\Property(property="company_certifications", type="string", example="ISO9001"),
     *             @OA\Property(property="company_ownership_type", type="string", example="Private"),
     *             @OA\Property(property="company_status", type="string", example="active"),
     *             @OA\Property(property="is_company", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Profile updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="company_name", type="string", example="Example Corp"),
     *                 @OA\Property(property="company_logo", type="string", example="images/logo.png")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the profile.")
     *         )
     *     )
     * )
     */
    public function company_profile_update(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found.');
        }
        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('User Account not found.');
        }
        $except = ['password', 'password_confirmation', 'avatar',];
        try {
            $u = Utils::fetch_post($u, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }


        $images = [];
        if (!empty($_FILES)) {
            $images = Utils::upload_images_2($_FILES, false);
        }
        if (!empty($images)) {
            $u->company_logo = 'images/' . $images[0];
        }
        $u->is_company = 'Yes';
        try {
            $u->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('Account not found.');
        }
        return $this->success($u,  "Profile updated successfully.");
    }


    /**
     * @OA\Get(
     *     path="/users/me",
     *     summary="Get current user profile.",
     *     description="Returns the authenticated user's profile details.",
     *     operationId="getCurrentUserProfile",
     *     tags={"User"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Profile details returned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Profile details"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User profile details",
     *                 additionalProperties=true,
     *                 example={
     *                     "id": 1,
     *                     "username": "johndoe",
     *                     "first_name": "John",
     *                     "last_name": "Doe",
     *                     "email": "john@example.com"
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */

    public function me()
    {
        $query = auth('api')->user();
        $u = User::find($query->id);
        if ($u == null) {
            return $this->error('Account not found.');
        }

        $path = $u->school_pay_account_id;
        $FullPath = public_path('storage/' . $path);
        //check if file exists
        if (
            strlen($u->school_pay_account_id) < 5 ||
            !file_exists($FullPath)
        ) {
            User::save_cv($u);
            $u = User::find($u->id);
        }

        return $this->success($u, $message = "Profile details", 200);
    }


    public function job_single(Request $request)
    {
        $job = Job::find($request->id);

        if (!$job) {
            return $this->error('Job not found. => #' . $request->id);
        }
        return $this->success($job, 'Job retrieved successfully.');
    }

    public function districts()
    {
        $districts = District::all();
        return $this->success($districts, 'Districts retrieved successfully.');
    }


    /**
     * @OA\Get(
     *     path="/jobs",
     *     summary="Get list of active jobs",
     *     description="Retrieves a paginated list of active jobs with optional search by title and status filtering.",
     *     operationId="getJobs",
     *     tags={"Job"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search jobs by title",
     *         required=false,
     *         @OA\Schema(type="string", example="Developer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by job status",
     *         required=false,
     *         @OA\Schema(type="string", example="Active")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 16)",
     *         required=false,
     *         @OA\Schema(type="integer", example=16)
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by job category ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="industry",
     *         in="query",
     *         description="Filter by industry",
     *         required=false,
     *         @OA\Schema(type="string", example="Technology")
     *     ),
     *     @OA\Parameter(
     *         name="district",
     *         in="query",
     *         description="Filter by district ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="deadline",
     *         in="query",
     *         description="Filter by application deadline (jobs with a deadline on or after the provided date)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2023-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="company",
     *         in="query",
     *         description="Filter by company name",
     *         required=false,
     *         @OA\Schema(type="string", example="Example Corp")
     *     ),
     *     @OA\Parameter(
     *         name="salary",
     *         in="query",
     *         description="Filter by minimum salary",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=50000)
     *     ),
     *     @OA\Parameter(
     *         name="employment_status",
     *         in="query",
     *         description="Filter by employment status (e.g., Full Time, Part Time, Contract, Internship)",
     *         required=false,
     *         @OA\Schema(type="string", example="Full Time")
     *     ),
     *     @OA\Parameter(
     *         name="workplace",
     *         in="query",
     *         description="Filter by workplace type (Onsite or Remote)",
     *         required=false,
     *         @OA\Schema(type="string", example="Onsite")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="Filter by gender requirement",
     *         required=false,
     *         @OA\Schema(type="string", example="Any")
     *     ),
     *     @OA\Parameter(
     *         name="experience_field",
     *         in="query",
     *         description="Filter by relevant experience field",
     *         required=false,
     *         @OA\Schema(type="string", example="Software Development")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort results by (Newest, Oldest, High Salary, Low Salary)",
     *         required=false,
     *         @OA\Schema(type="string", example="Newest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Software Engineer"),
     *                         @OA\Property(property="status", type="string", example="Active")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=16),
     *                 @OA\Property(property="total", type="integer", example=80)
     *             )
     *         )
     *     )
     * )
     */
    public function jobs(Request $request)
    {
        // Start building the query on active jobs
        $query = Job::where('status', 'Active');

        // Filter by search keyword (in the title)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Filter by category (assuming category_id stores the category)
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        // Filter by industry (if your jobs table has an 'industry' column)
        if ($request->filled('industry')) {
            $query->where('industry', $request->input('industry'));
        }

        // Filter by district
        if ($request->filled('district')) {
            $query->where('district_id', $request->input('district'));
        }

        // Filter by deadline (jobs with a deadline on or after the provided date)
        if ($request->filled('deadline')) {
            $query->whereDate('deadline', '>=', $request->input('deadline'));
        }

        // Filter by company (if your jobs table stores a company name)
        if ($request->filled('company')) {
            $query->where(['posted_by_id' => $request->input('company')]);
        }


        // Filter by posted_by_id  
        if ($request->filled('posted_by_id')) {
            $query->where(['posted_by_id' => $request->input('posted_by_id')]);
        }


        // Filter by posted_by_id  
        if ($request->filled('company_id')) {
            $query->where([]);
        }


        // Filter by salary – for example, jobs whose minimum salary is at least a given value
        if ($request->filled('salary')) {
            $query->where('minimum_salary', '>=', $request->input('salary'));
        }

        // Filter by employment status
        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->input('employment_status'));
        }

        // Filter by workplace
        if ($request->filled('workplace')) {
            $query->where('workplace', $request->input('workplace'));
        }

        // Filter by gender – if the filter is set to "Any" we ignore it
        if ($request->filled('gender') && $request->input('gender') !== 'Any') {
            $query->where('gender', $request->input('gender'));
        }

        // Filter by experience field (partial match)
        if ($request->filled('experience_field')) {
            $experienceField = $request->input('experience_field');
            $query->where('experience_field', 'LIKE', "%{$experienceField}%");
        }

        // Sorting logic based on 'sort' parameter
        if ($request->filled('sort')) {
            $sort = $request->input('sort');
            if ($sort === "Newest") {
                $query->orderBy('created_at', 'DESC');
            } elseif ($sort === "Oldest") {
                $query->orderBy('created_at', 'ASC');
            } elseif ($sort === "High Salary") {
                $query->orderBy('maximum_salary', 'DESC');
            } elseif ($sort === "Low Salary") {
                $query->orderBy('minimum_salary', 'ASC');
            } else {
                // Fallback ordering
                $query->orderBy('id', 'DESC');
            }
        } else {
            // Default ordering
            $query->orderBy('id', 'DESC');
        }

        // Paginate results (default 16 per page)
        $perPage = $request->input('per_page', 16);
        $jobs = $query->paginate($perPage);

        return $this->success($jobs, 'Success');
    }





    /**
     * @OA\Get(
     *     path="/company-jobs",
     *     summary="Get jobs posted by the authenticated company",
     *     description="Retrieves a list of jobs that the currently authenticated company has posted.",
     *     operationId="getCompanyJobs",
     *     tags={"Job"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search jobs by title",
     *         required=false,
     *         @OA\Schema(type="string", example="Developer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by job status",
     *         required=false,
     *         @OA\Schema(type="string", example="Active")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 16)",
     *         required=false,
     *         @OA\Schema(type="integer", example=16)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Software Engineer"),
     *                         @OA\Property(property="status", type="string", example="Active")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=16),
     *                 @OA\Property(property="total", type="integer", example=80)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving jobs.")
     *         )
     *     )
     * )
     */
    public function company_jobs(Request $request)
    {

        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }
        // Start building query
        $query = Job::where('status', 'Active')
            ->where('posted_by_id', $user->id);

        // Optional: search filter by title
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Optional: filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        // Order by newest
        $query->orderBy('id', 'DESC');

        // Paginate
        $perPage = $request->input('per_page', 10);
        $jobs = $query->paginate($perPage);

        return $this->success($jobs, 'Success');
    }




    /**
     * @OA\Get(
     *     path="/company-job-applications",
     *     summary="Get job applications by the authenticated company",
     *     description="Retrieves a list of job applications that the currently authenticated company has submitted.",
     *     operationId="getMyJobApplications",
     *     tags={"Job Application"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job applications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="job_id", type="integer", example=10),
     *                     @OA\Property(property="applicant_id", type="integer", example=1),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=20),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving job applications.")
     *         )
     *     )
     * )
     */
    public function my_job_applications(Request $request)
    {
        //my-job-applications 
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = JobApplication::where('applicant_id', $user->id);

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 100);
        $jobs = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($jobs, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/my-job-offers",
     *     summary="Get job offers for the authenticated user",
     *     description="Retrieves a list of job offers that the currently authenticated user has received.",
     *     operationId="getMyJobOffers",
     *     tags={"Job Offer"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="job_title", type="string", example="Software Engineer"),
     *                     @OA\Property(property="company_name", type="string", example="Example Corp"),
     *                     @OA\Property(property="salary", type="number", format="float", example=70000),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *                     @OA\Property(property="status", type="string", example="Pending"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=20),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving job offers.")
     *         )
     *     )
     * )
     */
    public function my_job_offers(Request $request)
    {
        //my-job-applications 
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = JobOffer::where('candidate_id', $user->id);

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 100);
        $jobs = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($jobs, 'Success');
    }


    /**
     * @OA\Get(
     *     path="/my-company-follows",
     *     summary="Get companies followed by the authenticated user",
     *     description="Retrieves a list of companies that the currently authenticated user is following.",
     *     operationId="getMyCompanyFollows",
     *     tags={"Company"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Companies followed retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="company_id", type="integer", example=10),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving company follows.")
     *         )
     *     )
     * )
     */
    public function my_company_follows(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = CompanyFollow::where('user_id', $user->id);

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 60);
        $follows = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($follows, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/company-job-offers",
     *     summary="Get job offers made by the authenticated company",
     *     description="Retrieves a list of job offers that the currently authenticated company has made.",
     *     operationId="getCompanyJobOffers",
     *     tags={"Job Offer"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="job_title", type="string", example="Software Engineer"),
     *                     @OA\Property(property="candidate_id", type="integer", example=10),
     *                     @OA\Property(property="company_id", type="integer", example=1),
     *                     @OA\Property(property="status", type="string", example="Pending"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=20),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving job offers.")
     *         )
     *     )
     * )
     */
    public function company_job_offers(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = JobOffer::where('company_id', $user->id);

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 100);
        $offers = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($offers, 'Success');
    }

    /**
     * @OA\Delete(
     *     path="/job-offers/{id}",
     *     summary="Delete a job offer",
     *     description="Deletes a job offer by its ID. Only the owner of the job offer can delete it.",
     *     operationId="deleteJobOffer",
     *     tags={"Job Offer"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the job offer to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offer deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job offer deleted successfully."),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only the owner can delete the job offer",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You are not authorized to delete this job offer.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Job offer not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job offer not found.")
     *         )
     *     )
     * )
     */
    public function delete_job_offer($id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $jobOffer = JobOffer::find($id);
        if (!$jobOffer) {
            return $this->error('Job offer not found.', 404);
        }

        if ($jobOffer->company_id !== $user->id) {
            return $this->error('You are not authorized to delete this job offer.', 403);
        }

        try {
            $jobOffer->delete();
        } catch (\Throwable $th) {
            return $this->error('An error occurred while deleting the job offer.', 500);
        }

        return $this->success(null, 'Job offer deleted successfully.');
    }

    /**
     * @OA\Put(
     *     path="/job-offers/{id}",
     *     summary="Update a job offer",
     *     description="Updates the details of an existing job offer.",
     *     operationId="updateJobOffer",
     *     tags={"Job Offer"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the job offer to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job offer update details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="job_title",
     *                 type="string",
     *                 example="Senior Software Engineer",
     *                 description="Updated job title"
     *             ),
     *             @OA\Property(
     *                 property="company_name",
     *                 type="string",
     *                 example="Updated Company Name",
     *                 description="Updated company name"
     *             ),
     *             @OA\Property(
     *                 property="salary",
     *                 type="number",
     *                 format="float",
     *                 example=80000,
     *                 description="Updated salary"
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2023-02-01",
     *                 description="Updated start date"
     *             ),
     *             @OA\Property(
     *                 property="job_description",
     *                 type="string",
     *                 example="Updated job description",
     *                 description="Updated job description"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offer updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job offer updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="job_title", type="string", example="Senior Software Engineer"),
     *                 @OA\Property(property="company_name", type="string", example="Updated Company Name"),
     *                 @OA\Property(property="salary", type="number", format="float", example=80000),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2023-02-01"),
     *                 @OA\Property(property="job_description", type="string", example="Updated job description")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid input.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Job offer not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Job offer not found.")
     *         )
     *     )
     * )
     */
    public function update_job_offer(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        $jobOffer = JobOffer::find($id);
        if (!$jobOffer) {
            return $this->error('Job offer not found.');
        }

        if ($jobOffer->company_id !== $user->id) {
            return $this->error('You are not authorized to update this job offer.', 403);
        }

        $except = ['id', 'candidate_id', 'company_id', 'slug'];
        try {
            $jobOffer = Utils::fetch_post($jobOffer, $except, $request->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        try {
            $jobOffer->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        $jobOffer = JobOffer::find($jobOffer->id);
        if (!$jobOffer) {
            return $this->error('Job offer not found.');
        }

        return $this->success($jobOffer, 'Job offer updated successfully.');
    }





    /**
     * @OA\Get(
     *     path="/my-job-applications",
     *     summary="Get job applications by the authenticated user",
     *     description="Retrieves a list of job applications that the currently authenticated user has submitted.",
     *     operationId="getMyJobApplications",
     *     tags={"Job Application"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job applications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="job_id", type="integer", example=10),
     *                     @OA\Property(property="applicant_id", type="integer", example=1),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=20),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving job applications.")
     *         )
     *     )
     * )
     */
    public function company_job_applications(Request $request)
    {
        //my-job-applications 
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = JobApplication::where('employer_id', $user->id);

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 100);
        $jobs = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($jobs, 'Success');
    }



    /**
     * @OA\Get(
     *     path="/cvs",
     *     summary="Get list of user CVs",
     *     description="Retrieves a paginated list of users' CVs, with optional filtering by name (search) and status.",
     *     operationId="getCVs",
     *     tags={"CV"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search user CVs by name",
     *         required=false,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by user status (e.g., active, inactive)",
     *         required=false,
     *         @OA\Schema(type="string", example="active")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 21)",
     *         required=false,
     *         @OA\Schema(type="integer", example=21)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User CVs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *                     @OA\Property(property="phone_number_1", type="string", example="+1234567890"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=21),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving CVs.")
     *         )
     *     )
     * )
     */
    public function cvs(Request $request)
    {

        // Start building query
        $query = User::where([]);

        // Optional: search filter by title
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // Optional: filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }
        //is_company 
        if ($request->filled('is_company')) {
            $is_company = $request->input('is_company');
            $query->where('is_company', $is_company);
        }

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 21);
        $jobs = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($jobs, 'Success');
    }


    /**
     * @OA\Get(
     *     path="/my-jobs",
     *     summary="Get jobs posted by the authenticated user",
     *     description="Retrieves a list of jobs that the currently authenticated user has posted.",
     *     operationId="getMyJobs",
     *     tags={"Job"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Jobs retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Software Engineer"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="category_id", type="integer", example=5),
     *                     @OA\Property(property="district_id", type="integer", example=10),
     *                     @OA\Property(property="employment_status", type="string", example="Full Time"),
     *                     @OA\Property(property="workplace", type="string", example="Onsite"),
     *                     @OA\Property(property="minimum_salary", type="number", format="float", example=50000),
     *                     @OA\Property(property="maximum_salary", type="number", format="float", example=70000),
     *                     @OA\Property(property="posted_by_id", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=20),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving jobs.")
     *         )
     *     )
     * )
     */

    public function my_jobs(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        // Start building query
        $query = Job::where('posted_by_id', $user->id);

        // Optional: search filter by title
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Optional: filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        // Order by newest (adjust as needed)
        $query->orderBy('id', 'DESC');

        // Paginate results (default to 10 per page)
        $perPage = $request->input('per_page', 10);
        $jobs = $query->paginate($perPage);

        // Return paginated data
        // 'data' contains "data, current_page, last_page, etc." from Laravel
        return $this->success($jobs, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/manifest",
     *     summary="Retrieve application manifest",
     *     description="Fetches the manifest data of the application, including version, name, description, build, and environment.",
     *     operationId="getManifest",
     *     tags={"Manifest"},
     *     @OA\Response(
     *         response=200,
     *         description="Manifest retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Manifest details"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="The manifest details",
     *                 @OA\Property(
     *                     property="version",
     *                     type="string",
     *                     example="1.0.0"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="My Application"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="This is a sample application manifest."
     *                 ),
     *                 @OA\Property(
     *                     property="build",
     *                     type="string",
     *                     example="2025-02-05-build"
     *                 ),
     *                 @OA\Property(
     *                     property="environment",
     *                     type="string",
     *                     example="production"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Manifest not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Manifest not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred while retrieving manifest"
     *             )
     *         )
     *     )
     * )
     */
    public function manifest()
    {

        $carbon = new Carbon();
        $TOP_CITIES = District::select('id', 'name', 'jobs_count', 'photo')
            ->orderBy('jobs_count', 'DESC')
            ->limit(10)
            ->get();
        $CATEGORIES = JobCategory::all();
        //Latest 50 jobs
        $TOP_JOBS = Job::where('status', 'Active')
            ->orderBy('id', 'DESC')
            ->limit(16)
            ->get();
        $manifest = [
            'LIVE_JOBS' => number_format(Job::where('status', 'Active')->count()),
            'VACANCIES' => number_format(Job::where('status', 'Active')->sum('vacancies_count')),
            'COMPANIES' => number_format(User::count()),
            'NEW_JOBS' => number_format(Job::where('status', 'Active')->where('created_at', '>=', $carbon->now()->subDays(7))->count()),
            'TOP_CITIES' => $TOP_CITIES,
            'CATEGORIES' => $CATEGORIES,
            'TOP_JOBS' => $TOP_JOBS,
        ];

        return $this->success($manifest, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/job-seeker-manifest",
     *     summary="Retrieve job seeker manifest",
     *     description="Fetches the manifest data for the authenticated job seeker, including CV views, profile completion percentage, job applications, job offers, and more.",
     *     operationId="getJobSeekerManifest",
     *     tags={"Manifest"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Manifest retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Success"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="The manifest details",
     *                 @OA\Property(property="cv_views", type="integer", example=10),
     *                 @OA\Property(property="profile_completion_percentage", type="integer", example=80),
     *                 @OA\Property(property="job_application_count", type="integer", example=5),
     *                 @OA\Property(property="job_application_pending", type="integer", example=2),
     *                 @OA\Property(property="job_application_accepted", type="integer", example=1),
     *                 @OA\Property(property="job_application_rejected", type="integer", example=2),
     *                 @OA\Property(
     *                     property="job_offers",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="job_title", type="string", example="Software Engineer"),
     *                         @OA\Property(property="company_name", type="string", example="Example Corp"),
     *                         @OA\Property(property="salary", type="number", format="float", example=70000),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *                         @OA\Property(property="status", type="string", example="Pending")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="job_applications",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="job_id", type="integer", example=10),
     *                         @OA\Property(property="applicant_id", type="integer", example=1),
     *                         @OA\Property(property="status", type="string", example="pending")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="upcoming_interviews",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="job_id", type="integer", example=10),
     *                         @OA\Property(property="applicant_id", type="integer", example=1),
     *                         @OA\Property(property="status", type="string", example="Interview")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="saved_jobs",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Software Engineer"),
     *                         @OA\Property(property="status", type="string", example="Active")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Account not found."
     *             )
     *         )
     *     )
     * )
     */
    public function job_seeker_manifest()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        $u = User::find($u->id);
        //manifest object
        $manifest = [];
        $manifest['cv_views'] = ViewRecord::where([
            'company_id' => $u->id,
            'type' => 'CV',
        ])->count();
        $manifest['profile_completion_percentage'] = $u->calculateProfileCompletion();
        $manifest['job_application_count'] = JobApplication::where('applicant_id', $u->id)->count();
        $manifest['job_application_pending'] = JobApplication::where('applicant_id', $u->id)->where('status', 'Pending')->count();
        $manifest['job_application_accepted'] = JobApplication::where('applicant_id', $u->id)->wherein('status', ['Interview', 'Hired'])->count();
        $manifest['job_application_rejected'] = JobApplication::where('applicant_id', $u->id)->wherein('status', ['Declined', 'Rejected', 'On Hold'])->count();
        $manifest['job_offers'] = JobOffer::where(['candidate_id' => $u->id])->limit(10)->get();
        $manifest['job_applications'] = JobApplication::where(['applicant_id' => $u->id])->limit(10)->get();
        $manifest['upcoming_interviews'] = JobApplication::where(['applicant_id' => $u->id, 'status' => 'Interview'])->limit(10)->get();
        $manifest['saved_jobs'] = Job::where([])->limit(10)->get();
        $manifest['job_applications_list'] = JobApplication::where(['applicant_id' => $u->id])->limit(100)->get();
        $manifest['company_follows'] = CompanyFollow::where(['user_id' => $u->id])->limit(100)->get();

        return $this->success($manifest, 'Success');
    }


    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get list of users",
     *     description="Retrieves a list of registered users with their basic details.",
     *     operationId="getUsers",
     *     tags={"User"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="username", type="string", example="johndoe"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="phone_number_1", type="string", example="+1234567890"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 description="Pagination details",
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving users.")
     *         )
     *     )
     * )
     */
    public function users()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }

        $admin_user_roles = AdminRoleUser::wherein('role_id', [1, 3, 4])
            ->get()
            ->pluck('user_id')
            ->toArray();

        $users = User::wherein('id', $admin_user_roles)
            ->get();

        return $this->success($users, $message = "Success", 200);
    }

    public function my_roles()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('Account not found');
        }
        $roles = $u->get_my_roles();
        return $this->success($roles, $message = "Success", 200);
        return $this->success($u->get_my_roles(), $message = "Success", 200);
    }

    public function laundry_orders()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }

        $orders = [];
        //admin
        //customer
        //driver
        //washer

        //if admin
        if ($u->isRole('admin')) {
            $orders = LaundryOrder::where([])
                ->get();
        }

        //if customer
        if ($u->isRole('customer')) {
            $orders = LaundryOrder::where([
                'user_id' => $u->id
            ])
                ->get();
        }

        //if driver
        if ($u->isRole('driver')) {
            $orders = LaundryOrder::where([
                'driver_id' => $u->id
            ])
                ->orWhere([
                    'delivery_driver_id' => $u->id
                ])
                ->get();
        }

        //if washer
        if ($u->isRole('washer')) {
            $orders[] = LaundryOrder::where([
                'washer_id' => $u->id
            ])
                ->get();
        }


        return $this->success($orders, $message = "Success", 200);
    }






    /**
     * @OA\Post(
     *     path="/users/login",
     *     summary="User login",
     *     description="Authenticate a user by verifying the username and password.",
     *     operationId="userLogin",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Pass username and password for login",
     *         @OA\JsonContent(
     *             required={"username", "password"},
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 example="johndoe",
     *                 description="The username, email, or phone number of the user"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 example="password123",
     *                 description="The user password"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Logged in successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="username", type="string", example="johndoe"),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="remember_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Username is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Wrong credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Wrong credentials.")
     *         )
     *     )
     * )
     */
    public function login(Request $r)
    {
        if ($r->username == null) {
            return $this->error('Username is required.');
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        $password = trim($r->password);
        if (strlen($password) < 3) {
            return $this->error('Password is invalid.');
        }

        $username = $r->username;
        if ($username == null) {
            return $this->error('Username is required.');
        }
        $username = trim($r->username);
        //check if username is less than 3
        if (strlen($username) < 3) {
            return $this->error('Username is invalid.');
        }

        $u = User::where('phone_number_1', $username)->first();
        if ($u == null) {
            $u = User::where('username', $username)->first();
        }
        if ($u == null) {
            $u = User::where('email', $username)->first();
        }
        if ($u == null) {
            return $this->error('User account not found.');
        }

        if ($u->status == 3) {
        }

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $u->id,
            'password' => trim($password),
        ]);

        if ($token == null) {
            return $this->error('Wrong credentials.');
        }

        $u->token = $token;
        $u->remember_token = $token;

        return $this->success($u, 'Logged in successfully.');
    }


    /**
     * @OA\Post(
     *     path="/users/register",
     *     summary="Register a new user",
     *     description="Registers a new user by validating the phone number, password, name, and optional email. On success, returns the user details along with a JWT token.",
     *     operationId="userRegister",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration details",
     *         @OA\JsonContent(
     *             required={"phone_number_1", "password", "name"},
     *             @OA\Property(
     *                 property="phone_number_1",
     *                 type="string",
     *                 example="+256789123456",
     *                 description="User's primary phone number"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="StrongPass123",
     *                 description="User password (must be at least 6 characters)"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="John Doe",
     *                 description="Full name of the user (must include at least first and last name)"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="john.doe@example.com",
     *                 description="Optional email address"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="phone_number_1", type="string", example="+256789123456"),
     *                 @OA\Property(property="username", type="string", example="+256789123456"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Phone number is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - User already exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User with same phone number already exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Failed to create account. Please try again.")
     *         )
     *     )
     * )
     */
    public function register(Request $r)
    {
        if ($r->phone_number_1 == null) {
            return $this->error('Phone number is required.');
        }

        $phone_number = Utils::prepare_phone_number(trim($r->phone_number));


        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Invalid phone number. -=>' . $phone_number);
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        //check if  password is greater than 6
        if (strlen($r->password) < 4) {
            return $this->error('Password must be at least 6 characters.');
        }

        if ($r->name == null) {
            return $this->error('Name is required.');
        }

        $email = trim($r->email);
        if ($email != null) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Invalid email address.');
            }
        }



        $u = Administrator::where('phone_number_1', $phone_number)
            ->orWhere('username', $phone_number)->first();
        if ($u != null) {
            return $this->error('User with same phone number already exists.');
        }

        $user = new Administrator();

        $name = $r->name;
        //replace all spaces with single space
        $name = preg_replace('!\s+!', ' ', $name);

        $x = explode(' ', $name);

        //check if last name is set
        if (!isset($x[1])) {
            return $this->error('Last name is required.');
        }

        if (
            isset($x[0]) &&
            isset($x[1])
        ) {
            $user->first_name = $x[0];
            $user->last_name = $x[1];
        } else {
            $user->first_name = $name;
        }

        //user with same email
        $u = Administrator::where('email', $email)->first();
        if ($u != null) {
            return $this->error('User with same email already exists.');
        }

        // same username
        $u = Administrator::where('username', $phone_number)->first();
        if ($u != null) {
            return $this->error('User with same phone number already exists.');
        }

        //same user name as email
        $u = Administrator::where('username', $email)->first();
        if ($u != null) {
            return $this->error('User with same email already exists.');
        }


        $user->phone_number_1 = $phone_number;
        $user->username = $phone_number;
        $user->email = $email;
        $user->name = $name;
        $user->password = password_hash(trim($r->password), PASSWORD_DEFAULT);
        if (!$user->save()) {
            return $this->error('Failed to create account. Please try again.');
        }

        $new_user = User::find($user->id);
        if ($new_user == null) {
            return $this->error('Account created successfully but failed to log you in.');
        }

        try {
            $new_user->send_mail_verification_code();
        } catch (\Throwable $th) {
            //throw $th;
        }

        Config::set('jwt.ttl', 60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $new_user->id,
            'password' => trim($r->password),
        ]);

        if ($token == null) {
            return $this->error('Account created successfully but failed to log you in.');
        }

        if (!$token) {
            return $this->error('Account created successfully but failed to log you in..');
        }

        $new_user->token = $token;
        $new_user->remember_token = $token;
        return $this->success($new_user, 'Account created successfully.');
    }


    /**
     * @OA\Post(
     *     path="/password-change",
     *     summary="Change user password",
     *     description="Allows an authenticated user to change their password by providing the current password and a new password.",
     *     operationId="changePassword",
     *     tags={"User"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password change request data",
     *         @OA\JsonContent(
     *             required={"current_password", "password"},
     *             @OA\Property(
     *                 property="current_password",
     *                 type="string",
     *                 format="password",
     *                 description="User's current password",
     *                 example="OldPassword123"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 description="New password (must be at least 2 characters)",
     *                 example="NewSecurePassword123"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Password changed successfully."),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="username", type="string", example="johndoe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing required fields or incorrect password",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Current password is incorrect.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while changing the password.")
     *         )
     *     )
     * )
     */
    public function password_change(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }

        if (
            $request->password == null ||
            strlen($request->password) < 2
        ) {
            return $this->error('Password is missing.');
        }

        //check if  current_password 
        if (
            $request->current_password == null ||
            strlen($request->current_password) < 2
        ) {
            return $this->error('Current password is missing.');
        }

        //check if  current_password
        if (
            !(password_verify($request->current_password, $u->password))
        ) {
            return $this->error('Current password is incorrect.');
        }

        $u->password = password_hash($request->password, PASSWORD_DEFAULT);
        $msg = "";
        $code = 1;
        try {
            $u->save();
            $msg = "Password changed successfully.";
            return $this->success($u, $msg, $code);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $code = 0;
            return $this->error($msg);
        }
        return $this->success(null, $msg, $code);
    }



    /**
     * @OA\Post(
     *     path="/email-verify",
     *     summary="Verify the user's email address",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email and verification code",
     *         @OA\JsonContent(
     *             required={"email", "code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Email verified successfully."),
     *             @OA\Property(property="code", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Verification failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Verification code is incorrect.")
     *         )
     *     )
     * )
     */
    public function email_verify(Request $request)
    {
        // Get the currently authenticated user via the API guard
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('User not found.');
        }

        // Re-fetch the user record from the database (assuming the model is Administrator)
        $user = Administrator::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        // Validate email input: it must be present and a valid email address.
        if (empty($request->email) || !filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email is missing or invalid.');
        }

        // Validate the verification code: ensure it is present and of a minimum length.
        if (empty($request->code) || strlen($request->code) < 3) {
            return $this->error('Verification code is missing.');
        }

        // Ensure the provided email matches the authenticated user's email.
        if ($user->email !== $request->email) {
            return $this->error('Provided email does not match our records.');
        }

        // Check the provided verification code against the user's stored code.
        // (Assumes the user has a "verification_code" field set when the code was sent.)
        if ($user->code !== $request->code) {
            return $this->error('Verification code is incorrect.');
        }

        // Mark the user as verified and clear the stored verification code.
        $user->verification = "Yes";
        $user->code = null;
        $user->code_sent_at = null;
        $user->code_is_sent = null;

        try {
            $user->save();
            $message = "Email verified successfully.";
            $user = User::find($user->id);
            // Return updated user data along with a success message and code.
            return $this->success($user, $message, 1);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }


    /**
     * @OA\Post(
     *     path="/delete-account",
     *     summary="Delete user account",
     *     description="Marks the authenticated user's account as deleted by updating the status to '3'.",
     *     operationId="deleteAccount",
     *     tags={"User"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Deleted successfully!"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the account.")
     *         )
     *     )
     * )
     */

    public function delete_profile(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }
        $u->status = '3';
        $u->save();
        return $this->success(null, $message = "Deleted successfully!", 1);
    }



    /**
     * @OA\Post(
     *     path="/post-media-upload",
     *     summary="Upload media files",
     *     description="Allows authenticated users to upload media files such as images. Supports file type validation and parent relationships.",
     *     operationId="uploadMedia",
     *     tags={"Media"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Media upload request data",
     *         @OA\MultipartContent(
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 description="Type of media being uploaded",
     *                 example="image"
     *             ),
     *             @OA\Property(
     *                 property="parent_id",
     *                 type="integer",
     *                 description="Parent entity ID associated with the media",
     *                 example=123
     *             ),
     *             @OA\Property(
     *                 property="parent_endpoint",
     *                 type="string",
     *                 description="Endpoint related to the parent entity (e.g., 'product', 'edit')",
     *                 example="product"
     *             ),
     *             @OA\Property(
     *                 property="product_id",
     *                 type="integer",
     *                 description="Associated product ID (if applicable)",
     *                 example=45
     *             ),
     *             @OA\Property(
     *                 property="note",
     *                 type="string",
     *                 description="Additional note about the media file",
     *                 example="This is a sample product image."
     *             ),
     *             @OA\Property(
     *                 property="file",
     *                 type="string",
     *                 format="binary",
     *                 description="The media file to upload"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="code", type="integer", example=1),
     *             @OA\Property(property="data", type="object", example={"file_url": "https://example.com/uploads/image123.jpg"}),
     *             @OA\Property(property="message", type="string", example="File uploaded successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing required fields",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="code", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Type is missing.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User authentication required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="code", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="code", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Failed to upload files.")
     *         )
     *     )
     * )
     */
    public function upload_media(Request $request)
    {

        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }

        //check for type
        if (
            !isset($request->type) ||
            $request->type == null ||
            (strlen(($request->type))) < 3
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Type is missing.",
            ]);
        }

        $administrator_id = $u->id;
        if (
            !isset($request->parent_id) ||
            $request->parent_id == null
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Local parent ID is missing. 1",
            ]);
        }

        if (
            !isset($request->parent_endpoint) ||
            $request->parent_endpoint == null ||
            (strlen(($request->parent_endpoint))) < 3
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Local parent ID endpoint is missing.",
            ]);
        }



        if (
            empty($_FILES)
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Files not found.",
            ]);
        }


        $images = Utils::upload_images_2($_FILES, false);
        $_images = [];

        if (empty($images)) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => 'Failed to upload files.',
                'data' => null
            ]);
        }


        $msg = "";
        foreach ($images as $src) {

            if ($request->parent_endpoint == 'edit') {
                $img = Image::find($request->local_parent_id);
                if ($img) {
                    return Utils::response([
                        'status' => 0,
                        'code' => 0,
                        'message' => "Original photo not found",
                    ]);
                }
                $img->src =  $src;
                $img->thumbnail =  null;
                $img->save();
                return Utils::response([
                    'status' => 1,
                    'code' => 1,
                    'data' => json_encode($img),
                    'message' => "File updated.",
                ]);
            }


            $img = new Image();
            $img->administrator_id =  $administrator_id;
            $img->src =  $src;
            $img->thumbnail =  null;
            $img->parent_endpoint =  $request->parent_endpoint;
            $img->type =  $request->type;
            $img->product_id =  $request->product_id;
            $img->parent_id =  $request->parent_id;
            $img->size = 0;
            $img->note = '';
            if (
                isset($request->note)
            ) {
                $img->note =  $request->note;
                $msg .= "Note not set. ";
            }



            $img->save();
            $_images[] = $img;
        }
        //Utils::process_images_in_backround();
        return Utils::response([
            'status' => 1,
            'code' => 1,
            'data' => json_encode($_POST),
            'message' => "File uploaded successfully.",
        ]);
    }

    /**
     * @OA\Post(
     *     path="/view-record-create",
     *     summary="Create a new view record",
     *     description="Creates a view record for a specific type and item by the authenticated user. Only one record per day is allowed for the same viewer_id, type, and item_id.",
     *     operationId="viewRecordCreate",
     *     tags={"ViewRecord"},
     *     security={{ "apiAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="View record details",
     *         @OA\JsonContent(
     *             required={"type", "item_id"},
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="company",
     *                 description="The type of item being viewed (e.g. 'company', 'job', 'profile', etc.)"
     *             ),
     *             @OA\Property(
     *                 property="item_id",
     *                 type="integer",
     *                 example=123,
     *                 description="The ID of the item being viewed"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="View record created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="View record created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="company"),
     *                 @OA\Property(property="viewer_id", type="integer", example=10),
     *                 @OA\Property(property="item_id", type="integer", example=123),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-02-08T12:34:56Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing or invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid input: type is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Already viewed today",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You have already viewed this item today.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User not found or invalid token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     )
     * )
     */
    public function view_record_create(Request $r)
    {
        // Ensure user is authenticated
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found.');
        }

        // Validate required fields
        if (!$r->filled('type') || !$r->filled('item_id')) {
            return $this->error('type and item_id are required.');
        }

        $type = trim($r->type);
        $itemId = (int) trim($r->item_id);

        if (strlen($type) < 2 || $itemId < 1) {
            return $this->error('Invalid type or item_id.');
        }

        // Check if we already have a record for this user, type, item_id on the same day
        // Using Carbon for date check
        $today = \Carbon\Carbon::now()->startOfDay();

        // Adjust the model's namespace if needed, e.g. App\Models\ViewRecord
        $existing = \App\Models\ViewRecord::where('viewer_id', $user->id)
            ->where('type', $type)
            ->where('item_id', $itemId)
            // Compare created_at >= today
            ->where('created_at', '>=', $today)
            ->first();

        if ($existing) {
            return $this->error('You have already viewed this item today.');
        }

        // Create a new record
        $record = new \App\Models\ViewRecord();
        $record->type = $type;
        $record->viewer_id = $user->id;
        $record->item_id = $itemId;

        try {
            $record->save();
        } catch (\Throwable $th) {
            return $this->error("Failed to create view record: " . $th->getMessage());
        }

        // Return success with the newly created record
        $freshRecord = \App\Models\ViewRecord::find($record->id);
        return $this->success($freshRecord, 'View record created successfully.');
    }

    /**
     * @OA\Get(
     *     path="/view-records",
     *     summary="Fetch view records for the authenticated viewer",
     *     description="Retrieves a paginated list of view records of a specific type for the authenticated user. The type query parameter is required.",
     *     operationId="getViewRecords",
     *     tags={"ViewRecord"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="The type of item to fetch views for (e.g., 'company', 'job', 'profile')",
     *         required=true,
     *         @OA\Schema(type="string", example="company")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="View records retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="View records retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Paginated view records data",
     *                 additionalProperties=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing type parameter",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Type parameter is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     )
     * )
     */
    public function view_records(Request $r)
    {
        // Ensure the user is authenticated
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found.');
        }

        // Require the "type" query parameter
        $type = $r->input('type');
        if (!$type) {
            return $this->error('Type parameter is required.');
        }

        // Optionally support pagination; default to 10 per page
        $perPage = $r->input('per_page', 100);

        try {
            // Retrieve paginated view records for this viewer and type
            $records = \App\Models\ViewRecord::where('viewer_id', $user->id)
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } catch (\Throwable $e) {
            return $this->error("Failed to fetch view records: " . $e->getMessage());
        }

        return $this->success($records, 'View records retrieved successfully.');
    }


    /**
     * @OA\Get(
     *     path="/company-view-records",
     *     summary="Fetch view records for the authenticated company",
     *     description="Retrieves a paginated list of view records for the authenticated company filtered by type.",
     *     operationId="getCompanyViewRecords",
     *     tags={"ViewRecord"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="The type of view records to fetch (e.g., 'job', 'profile', 'company')",
     *         required=true,
     *         @OA\Schema(type="string", example="job")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="View records retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="View records retrieved successfully."),
     *             @OA\Property(property="data", type="object", additionalProperties=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Missing type parameter",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Type parameter is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Account not found.")
     *         )
     *     )
     * )
     */
    public function company_view_records(Request $r)
    {
        // Ensure the user is authenticated
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found.');
        }

        // Require the "type" query parameter
        $type = $r->input('type');
        if (!$type) {
            return $this->error('Type parameter is required.');
        }

        // Optionally support pagination (default 10 per page)
        $perPage = $r->input('per_page', 100);

        try {
            // Retrieve view records for the authenticated company (assuming company_id is stored in the view record)
            $records = \App\Models\ViewRecord::where('company_id', $user->id)
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } catch (\Throwable $e) {
            return $this->error("Failed to fetch view records: " . $e->getMessage());
        }

        return $this->success($records, 'View records retrieved successfully.');
    }
}
