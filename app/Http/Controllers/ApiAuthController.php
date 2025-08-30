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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
            'email-verify',
            'send-mail-verification-code',
            'password-reset-submit',
        ]]);
    }


    /**
     * Create or update a service.
     *
     * Endpoint: POST /api/service-create
     */
    public function service_create(Request $r)
    {
        // 1. Authenticate provider
        $user = auth('api')->user();
        if ($user === null) {
            return $this->error('Account not found.');
        }

        // 2. Determine if editing or creating
        $serviceId = $r->input('id');
        if (!empty($serviceId)) {
            $service = Service::find((int)$serviceId);
            if ($service === null) {
                return $this->error('Service not found.');
            }
            // Ensure the authenticated user owns this service
            if ((string)$service->provider_id !== (string)$user->id) {
                return $this->error('Unauthorized to edit this service.');
            }
        } else {
            $service = new Service();
        }

        // 3. Populate service fields, excluding file fields and protected columns
        $except = [
            'id',
            'provider_id',
            'cover_image',
            'gallery',
        ];

        try {
            $service = Utils::fetch_post($service, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        // 4. Assign provider_id for new services (or preserve existing for updates)
        $service->provider_id = $user->id;

        // 5. Handle file uploads
        $uploadedCover = [];
        $uploadedGallery = [];

        if (!empty($_FILES)) {
            try {
                // If a cover_image was sent, process it
                if (isset($_FILES['cover_image'])) {
                    $uploadedCover = Utils::upload_images_2(['cover_image' => $_FILES['cover_image']], false);
                    if (!empty($uploadedCover)) {
                        // Assume upload_images_2 returns array of filenames/paths
                        $service->cover_image = 'images/' . $uploadedCover[0];
                    }
                }

                // If gallery images were sent (as gallery[0], gallery[1], …), process them all
                $galleryFiles = [];
                foreach ($_FILES as $key => $fileInfo) {
                    if (strpos($key, 'gallery') === 0) {
                        $galleryFiles[$key] = $fileInfo;
                    }
                }
                if (!empty($galleryFiles)) {
                    $uploadedGallery = Utils::upload_images_2($galleryFiles, false);
                    if (!empty($uploadedGallery)) {
                        // Save as JSON array of saved paths
                        $galleryPaths = array_map(fn($fname) => 'images/' . $fname, $uploadedGallery);
                        $service->gallery = json_encode($galleryPaths);
                    }
                }
            } catch (\Throwable $th) {
                // If upload fails, continue—fields remain as-is or empty
            }
        }

        // 6. Save to database
        try {
            $service->save();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }

        // 7. Fetch fresh instance to return
        $fresh = Service::find($service->id);
        if ($fresh === null) {
            return $this->error('Failed to retrieve saved service.');
        }

        // 8. Return success response
        return $this->success($fresh, $serviceId ? 'Service updated successfully.' : 'Service created successfully.');
    }

    /**
     * Standardized error response
     */
    protected function error(string $message, $data = null)
    {
        return response()->json([
            'code'    => 0,
            'message' => $message,
            'data'    => $data,
        ], 400);
    }

    /**
     * Standardized success response
     */
    protected function success($data = null, string $message = '')
    {
        return response()->json([
            'code'    => 1,
            'message' => $message,
            'data'    => $data,
        ]);
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
        if (!empty($_FILES)) {
            try {
                if (!empty($_FILES)) {
                    $attachments = Utils::upload_images_2($_FILES, false);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        //if $attachments not empty, save as json
        if (!empty($attachments)) {
            $jobAppication->attachments = json_encode($attachments);
        }

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
        try {
            $authUser = auth('api')->user();
            if (!$authUser) {
                return $this->error('Account not found.');
            }

            $user = User::find($authUser->id);
            if (!$user) {
                return $this->error('User Account not found.');
            }

            // Get all allowed company fields for update - matching database schema
            $allowedFields = [
                // Basic company info
                'name', 'description', 'industry', 'mission', 'vision', 'values',
                
                // Database company fields (from migration)
                'company_name', 'company_year_of_establishment', 'company_employees_range',
                'company_country', 'company_address', 'company_district_id', 'company_sub_county_id',
                'company_main_category_id', 'company_sub_category_id', 'company_phone_number',
                'company_description', 'company_trade_license_no', 'company_website_url',
                'company__email', 'company__phone', 'company_has_accessibility', 
                'company_has_disability_inclusion_policy', 'company_tax_id',
                'company_facebook_url', 'company_linkedin_url', 'company_operating_hours',
                'company_certifications', 'company_ownership_type', 'company_status',
                
                // Direct mapping fields (for backward compatibility)
                'website', 'email', 'phone', 'address', 'city', 'country', 'founded_year',
                'linkedin_url', 'facebook_url', 'twitter_url', 'instagram_url',
                'employee_count', 'has_accessibility', 'has_disability_inclusion', 'company_size'
            ];

            $updateData = $r->only($allowedFields);
            
            // Map mobile app fields to database fields for consistency
            $fieldMappings = [
                'name' => 'company_name',
                'description' => 'company_description', 
                'website' => 'company_website_url',
                'phone' => 'company_phone_number',
                'email' => 'company__email',
                'address' => 'company_address',
                'country' => 'company_country',
                'founded_year' => 'company_year_of_establishment',
                'employee_count' => 'company_employees_range',
                'company_size' => 'company_employees_range',
                'linkedin_url' => 'company_linkedin_url',
                'facebook_url' => 'company_facebook_url',
            ];

            // Apply field mappings
            foreach ($fieldMappings as $mobileField => $dbField) {
                if (isset($updateData[$mobileField])) {
                    $updateData[$dbField] = $updateData[$mobileField];
                    unset($updateData[$mobileField]); // Remove original field
                }
            }
            
            // Handle company logo upload - consistent with profile avatar
            $logoUploaded = false;
            
            // Method 1: Laravel file upload (React.js)
            if ($r->hasFile('company_logo')) {
                $logo = $r->file('company_logo');
                $ext = $logo->getClientOriginalExtension();
                $fileName = time() . '-' . rand(100000, 1000000) . '.' . $ext;
                
                // Store directly in public/storage/images/
                $destinationPath = public_path('storage/images');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $logo->move($destinationPath, $fileName);
                $updateData['company_logo'] = 'images/' . $fileName;
                $logoUploaded = true;
            }
            
            // Method 2: Raw $_FILES upload (Mobile app)
            if (!$logoUploaded && !empty($_FILES) && isset($_FILES['company_logo'])) {
                try {
                    $file = $_FILES['company_logo'];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = time() . '-' . rand(100000, 1000000) . '.' . $ext;
                        
                        // Store directly in public/storage/images/
                        $destinationPath = public_path('storage/images');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }
                        
                        $destination = $destinationPath . '/' . $fileName;
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $updateData['company_logo'] = 'images/' . $fileName;
                            $logoUploaded = true;
                        }
                    }
                } catch (\Throwable $th) {
                    Log::error('Company logo upload failed: ' . $th->getMessage());
                }
            }

            // Handle company banner upload (similar to logo)
            if ($r->hasFile('company_banner')) {
                $banner = $r->file('company_banner');
                $ext = $banner->getClientOriginalExtension();
                $fileName = time() . '-' . rand(100000, 1000000) . '.' . $ext;
                
                $destinationPath = public_path('storage/images');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $banner->move($destinationPath, $fileName);
                $updateData['company_banner'] = 'images/' . $fileName;
            }

            // Set company flag
            $updateData['is_company'] = 'Yes';

            // Remove null and empty values, but keep boolean false values
            $updateData = array_filter($updateData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update user with company data
            try {
                $user = Utils::fetch_post($user, [], $updateData);
                $user->save();
            } catch (\Throwable $th) {
                return $this->error('Failed to update company profile: ' . $th->getMessage());
            }

            // Refresh user data
            $user = User::find($user->id);
            if (!$user) {
                return $this->error('Account not found after update.');
            }

            return $this->success($user, "Company profile updated successfully.");

        } catch (\Throwable $th) {
            return $this->error('An error occurred: ' . $th->getMessage());
        }
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
     *     path="/my-job-applications",
     *     summary="Get user's job applications",
     *     description="Retrieves all job applications submitted by the authenticated user",
     *     operationId="getMyJobApplications",
     *     tags={"Job Applications"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for job titles",
     *         required=false,
     *         @OA\Schema(type="string", example="Developer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by application status",
     *         required=false,
     *         @OA\Schema(type="string", example="Pending")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job applications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     )
     * )
     */
    public function my_job_applications(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        $query = JobApplication::where('applicant_id', $user->id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('job_text', 'LIKE', "%{$search}%");
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Sort by newest first
        $query->orderBy('id', 'DESC');

        // Pagination
        $perPage = $request->input('per_page', 10);
        $applications = $query->paginate($perPage);

        return $this->success($applications, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/company-job-applications",
     *     summary="Get company's job applications",
     *     description="Retrieves all job applications for the authenticated company's jobs",
     *     operationId="getCompanyJobApplications",
     *     tags={"Company", "Job Applications"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for applicant names or job titles",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by application status",
     *         required=false,
     *         @OA\Schema(type="string", example="Pending")
     *     ),
     *     @OA\Parameter(
     *         name="job_id",
     *         in="query",
     *         description="Filter by specific job ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job applications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     )
     * )
     */
    public function company_job_applications(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        $query = JobApplication::where('employer_id', $user->id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('applicant_text', 'LIKE', "%{$search}%")
                    ->orWhere('job_text', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Job ID filter
        if ($request->filled('job_id')) {
            $query->where('job_id', $request->input('job_id'));
        }

        // Sort by newest first
        $query->orderBy('id', 'DESC');

        // Pagination
        $perPage = $request->input('per_page', 10);
        $applications = $query->paginate($perPage);

        return $this->success($applications, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/company-job-offers",
     *     summary="Get company's job offers",
     *     description="Retrieves all job offers sent by the authenticated company",
     *     operationId="getCompanyJobOffers",
     *     tags={"Company", "Job Offers"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for job titles or candidate names",
     *         required=false,
     *         @OA\Schema(type="string", example="Developer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by offer status",
     *         required=false,
     *         @OA\Schema(type="string", example="Pending")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     )
     * )
     */
    public function company_job_offers(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        $query = JobOffer::where('company_id', $user->id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('candidate_text', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Sort by newest first
        $query->orderBy('id', 'DESC');

        // Pagination
        $perPage = $request->input('per_page', 10);
        $offers = $query->paginate($perPage);

        return $this->success($offers, 'Success');
    }

    /**
     * @OA\Get(
     *     path="/company-recent-activities",
     *     summary="Get company recent activities",
     *     description="Retrieves recent activities for the authenticated company including job applications, job postings, and followers.",
     *     operationId="getCompanyRecentActivities",
     *     tags={"Company"},
     *     security={{ "apiAuth": {} }},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of activities to return (default: 20)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recent activities retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="application"),
     *                     @OA\Property(property="title", type="string", example="New job application received"),
     *                     @OA\Property(property="description", type="string", example="Application for Software Developer"),
     *                     @OA\Property(property="time", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
     *                     @OA\Property(property="icon", type="string", example="fileText")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     )
     * )
     */
    public function company_recent_activities(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Account not found');
        }

        $limit = $request->input('limit', 20);
        $activities = [];

        try {
            // Get recent job applications
            $applications = JobApplication::where('employer_id', $user->id)
                ->orderBy('id', 'DESC')
                ->take(5)
                ->get();

            foreach ($applications as $app) {
                $activities[] = [
                    'type' => 'application',
                    'title' => 'New job application received',
                    'description' => 'Application for ' . ($app->job_text ?? 'Unknown Position'),
                    'time' => $app->created_at,
                    'icon' => 'fileText',
                    'data' => $app
                ];
            }

            // Get recent jobs posted
            $jobs = Job::where('posted_by_id', $user->id)
                ->orderBy('id', 'DESC')
                ->take(3)
                ->get();

            foreach ($jobs as $job) {
                $activities[] = [
                    'type' => 'job',
                    'title' => 'Job posting published',
                    'description' => ($job->title ?? 'Unknown Position') . ' is now live',
                    'time' => $job->created_at,
                    'icon' => 'briefcase',
                    'data' => $job
                ];
            }

            // Get recent followers
            $followers = CompanyFollow::where('company_id', $user->id)
                ->orderBy('id', 'DESC')
                ->take(3)
                ->get();

            foreach ($followers as $follower) {
                $activities[] = [
                    'type' => 'follower',
                    'title' => 'New follower',
                    'description' => ($follower->user_text ?? 'Someone') . ' started following you',
                    'time' => $follower->created_at,
                    'icon' => 'users',
                    'data' => $follower
                ];
            }

            // Sort by time (newest first)
            usort($activities, function ($a, $b) {
                return strtotime($b['time']) - strtotime($a['time']);
            });

            // Limit results
            $activities = array_slice($activities, 0, $limit);

            return $this->success($activities, 'Success');
        } catch (Exception $e) {
            return $this->error('Error fetching activities: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/users/login",
     *     summary="User login",
     *     description="Authenticates a user and returns a JWT token",
     *     operationId="userLogin",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Login credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"username", "password"},
     *             @OA\Property(property="username", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="code", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid credentials")
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
        config(['jwt.ttl' => 60 * 24 * 30 * 365]);

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
     *     summary="User registration",
     *     description="Registers a new user account",
     *     operationId="userRegister",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Registration data",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="phone", type="string", example="+256700000000"),
     *             @OA\Property(property="district_id", type="integer", example=1)
     *         )
     *     )
     * )
     */
    public function register(Request $r)
    {
        // Phone number is now optional to comply with Apple App Store guidelines
        $phone_number = null;
        $phone_input = $r->phone_number_1 ?: $r->phone_number;
        
        // Only process phone number if it's actually provided and not empty
        if (!empty($phone_input) && trim($phone_input) !== '') {
            $phone_number = Utils::prepare_phone_number(trim($phone_input));
            
            if (!Utils::phone_number_is_valid($phone_number)) {
                return $this->error('Invalid phone number. -=>' . $phone_number);
            }
            
            // Check if phone number already exists (only check for non-null phone numbers)
            $u = Administrator::where('phone_number_1', $phone_number)
                ->whereNotNull('phone_number_1')
                ->where('phone_number_1', '!=', '')
                ->first();
            if ($u != null) {
                return $this->error('User with same phone number already exists.');
            }
            
            // Also check username (in case phone is used as username)
            $u2 = Administrator::where('username', $phone_number)
                ->whereNotNull('username')
                ->where('username', '!=', '')
                ->first();
            if ($u2 != null) {
                return $this->error('User with same phone number already exists.');
            }
        }        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        //check if  password is greater than 6
        if (strlen($r->password) < 4) {
            return $this->error('Password must be at least 6 characters.');
        }

        if ($r->name == null) {
            return $this->error('Name is required.');
        }

        // Email is now required if phone number is not provided
        $email = trim($r->email);
        if ($email == null || $email == '') {
            if ($phone_number == null) {
                return $this->error('Either email or phone number is required.');
            }
        } else {
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
        if ($email != null) {
            $u = Administrator::where('email', $email)->first();
            if ($u != null) {
                return $this->error('User with same email already exists.');
            }

            //same user name as email
            $u = Administrator::where('username', $email)->first();
            if ($u != null) {
                return $this->error('User with same email already exists.');
            }
        }

        $user->phone_number_1 = $phone_number;
        // Use email as username if available, otherwise use phone number
        $user->username = $email != null ? $email : $phone_number;
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
        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);
        config(['jwt.ttl' => 60 * 24 * 30 * 365]);

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
     *     path="/profile",
     *     summary="Update user profile",
     *     description="Updates the authenticated user's profile information",
     *     operationId="updateProfile",
     *     tags={"User"},
     *     security={{"apiAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Profile data to update",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="phone", type="string", example="+256700000000"),
     *             @OA\Property(property="bio", type="string", example="Software developer"),
     *             @OA\Property(property="location", type="string", example="Kampala")
     *         )
     *     )
     * )
     */
    public function profile_update(Request $request)
    {
        try {
            $authUser = auth('api')->user();

            if (!$authUser) {
                return $this->error('User not authenticated');
            }

            // Get the actual User model
            $user = User::find($authUser->id);
            if (!$user) {
                return $this->error('User not found');
            }

            // Get all allowed fields for update - comprehensive list based on user model
            $allowedFields = [
                // Basic info
                'username', 'name', 'first_name', 'last_name', 'email',
                'phone_number_1', 'phone_number_2', 'avatar',
                
                // Personal details
                'title', 'date_of_birth', 'place_of_birth', 'sex', 'marital_status',
                'nationality', 'religion', 'blood_group', 'height', 'weight',
                
                // Address info
                'home_address', 'current_address', 'district_id',
                
                // Contact info
                'emergency_person_name', 'emergency_person_phone',
                'spouse_name', 'spouse_phone', 'father_name', 'father_phone',
                'mother_name', 'mother_phone', 'languages',
                
                // ID info
                'national_id_number', 'passport_number', 'tin', 'nssf_number',
                
                // Banking info
                'bank_name', 'bank_account_number',
                
                // Education info
                'primary_school_name', 'primary_school_year_graduated',
                'seconday_school_name', 'seconday_school_year_graduated',
                'high_school_name', 'high_school_year_graduated',
                'degree_university_name', 'degree_university_year_graduated',
                'masters_university_name', 'masters_university_year_graduated',
                'phd_university_name', 'phd_university_year_graduated',
                'diploma_school_name', 'diploma_year_graduated',
                'certificate_school_name', 'certificate_year_graduated',
                
                // Career info
                'intro', 'career_summary', 'objective', 'special_qualification',
                'present_salary', 'expected_salary', 'expected_job_level',
                'expected_job_nature', 'preferred_job_location',
                'preferred_job_category', 'preferred_job_category_other',
                'preferred_job_districts', 'preferred_job_abroad', 'preferred_job_countries',
                
                // Disability info
                'has_disability', 'is_registered_on_disability', 'disability_type',
                'dificulty_to_see', 'dificulty_to_hear', 'dificulty_to_walk',
                'dificulty_to_speak', 'dificulty_display_on_cv'
            ];

            $updateData = $request->only($allowedFields);

            // Handle field mappings for backward compatibility
            if ($request->has('phone')) {
                $updateData['phone_number_1'] = $request->input('phone');
            }
            if ($request->has('bio')) {
                $updateData['intro'] = $request->input('bio');
            }
            if ($request->has('location')) {
                $updateData['current_address'] = $request->input('location');
            }

            // Handle file uploads (avatar) - Ensure consistent path: public/storage/images/
            $avatarUploaded = false;
            
            // Method 1: Laravel file upload (React.js)
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $ext = $avatar->getClientOriginalExtension();
                $fileName = time() . '-' . rand(100000, 1000000) . '.' . $ext;
                
                // Store directly in public/storage/images/
                $destinationPath = public_path('storage/images');
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $avatar->move($destinationPath, $fileName);
                $updateData['avatar'] = 'images/' . $fileName;
                $avatarUploaded = true;
            }
            
            // Method 2: Raw $_FILES upload (Mobile app)
            if (!$avatarUploaded && !empty($_FILES) && isset($_FILES['avatar'])) {
                try {
                    $file = $_FILES['avatar'];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = time() . '-' . rand(100000, 1000000) . '.' . $ext;
                        
                        // Store directly in public/storage/images/
                        $destinationPath = public_path('storage/images');
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }
                        
                        $destination = $destinationPath . '/' . $fileName;
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $updateData['avatar'] = 'images/' . $fileName;
                            $avatarUploaded = true;
                        }
                    }
                } catch (\Throwable $th) {
                    // Continue without error - avatar upload failed but other fields can still update
                    Log::error('Avatar upload failed: ' . $th->getMessage());
                }
            }

            // Remove null and empty values, but keep boolean false values
            $updateData = array_filter($updateData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Use Utils to update user
            try {
                $user = Utils::fetch_post($user, [], $updateData);
                $user->save();
            } catch (\Throwable $th) {
                return $this->error('Profile update failed: ' . $th->getMessage());
            }

            return $this->success($user, 'Profile updated successfully');
        } catch (Exception $e) {
            return $this->error('Profile update failed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/download-cv/{id}",
     *     summary="Download user CV",
     *     description="Downloads the CV for a specific user (public access)",
     *     operationId="downloadCv",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CV file download or redirect",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function download_cv($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Fallback: redirect to the web route for CV generation (public)
            return redirect('/get-cv?id=' . $id);

        } catch (Exception $e) {
            return response()->json(['message' => 'CV download failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get users list",
     *     description="Retrieves a list of users with optional filtering",
     *     operationId="getUsers",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of users to return",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of users to skip",
     *         @OA\Schema(type="integer", example=0)
     *     )
     * )
     */
    public function users(Request $request)
    {
        try {
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            $users = User::select(['id', 'name', 'email', 'phone', 'avatar', 'bio', 'location', 'created_at'])
                ->offset($offset)
                ->limit($limit)
                ->orderBy('id', 'DESC')
                ->get();

            return $this->success($users, 'Users retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving users: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/cvs",
     *     summary="Get CVs list",
     *     description="Retrieves a list of user CVs",
     *     operationId="getCvs",
     *     tags={"CV"}
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
     *     summary="Get user's jobs",
     *     description="Retrieves jobs posted by the authenticated user",
     *     operationId="getMyJobs",
     *     tags={"Job"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function my_jobs(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $jobs = Job::where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->get();

            return $this->success($jobs, 'My jobs retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving jobs: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/my-roles",
     *     summary="Get user roles",
     *     description="Retrieves the authenticated user's roles",
     *     operationId="getMyRoles",
     *     tags={"User"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function my_roles(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $roles = AdminRoleUser::where('user_id', $user->id)->get();

            return $this->success($roles, 'User roles retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving roles: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/password-change",
     *     summary="Change user password",
     *     description="Changes the authenticated user's password",
     *     operationId="changePassword",
     *     tags={"User"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function password_change(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $rules = [
                'current_password' => 'required',
                'password' => 'required|min:4'
            ];


            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error($validator->errors());
            }

            //check if password not same as current password
            if (password_verify($request->password, $user->password)) {
                return $this->error('New password cannot be the same as current password');
            }

            // Check current password
            if (!password_verify($request->current_password, $user->password)) {
                return $this->error('Current password is incorrect');
            }

            $user->update([
                'password' => bcrypt($request->password)
            ]);

            return $this->success(null, 'Password changed successfully');
        } catch (Exception $e) {
            return $this->error('Password change failed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/email-verify",
     *     summary="Verify email",
     *     description="Verifies user's email with a code",
     *     operationId="verifyEmail",
     *     tags={"Authentication"}
     * )
     */
    public function email_verify(Request $request)
    {
        try {
            $rules = [
                'email' => 'required|email',
                'code' => 'required|string'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error($validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->error('User not found');
            }

            if ($user->code !== $request->code) {
                return $this->error('Invalid verification code');
            }

            $user->update([
                'email_verified_at' => now(),
                'code' => null,
                'code_sent_at' => null
            ]);

            return $this->success(null, 'Email verified successfully');
        } catch (Exception $e) {
            return $this->error('Email verification failed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/delete-account",
     *     summary="Delete user account",
     *     description="Deletes the authenticated user's account",
     *     operationId="deleteAccount",
     *     tags={"User"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function delete_profile(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $rules = [
                'password' => 'required',
                'reason' => 'nullable|string'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error($validator->errors());
            }

            // Verify password
            if (!password_verify($request->password, $user->password)) {
                return $this->error('Password is incorrect');
            }

            // Log the deletion reason if provided
            if ($request->reason) {
                Log::info("User {$user->id} deleted account. Reason: {$request->reason}");
            }

            $user->delete();

            return $this->success(null, 'Account deleted successfully');
        } catch (Exception $e) {
            return $this->error('Account deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete user account and related data robustly (no PIN, just confirmation)
     */
    public function delete_account_api(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->error('User not authenticated');
            }
            if ($user->id == 1) {
                return $this->error('Super admin account cannot be deleted.');
            }

            // Try to delete related data, ignore errors
            $userId = $user->id;
            $errors = [];
            // Chat heads
            try {
                \App\Models\ChatHead::where('user_1_id', $userId)->orWhere('user_2_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'chat_heads';
            }
            // Chat messages
            try {
                \App\Models\ChatMessage::where('sender_id', $userId)->orWhere('receiver_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'chat_messages';
            }
            // Job applications
            try {
                \App\Models\JobApplication::where('applicant_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'job_applications';
            }
            // Jobs
            try {
                \App\Models\Job::where('posted_by_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'jobs';
            }
            // Services
            try {
                \App\Models\Service::where('provider_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'services';
            }
            // Company follows
            try {
                \App\Models\CompanyFollow::where('user_id', $userId)->orWhere('company_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'company_follows';
            }
            // View records
            try {
                \App\Models\ViewRecord::where('viewer_id', $userId)->orWhere('item_id', $userId)->delete();
            } catch (\Throwable $e) {
                $errors[] = 'view_records';
            }
            // Add more related deletions as needed

            // Finally, delete the user
            try {
                $user->delete();
            } catch (\Throwable $e) {
                return $this->error('Failed to delete user account.');
            }

            // Logout user (invalidate token)
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Throwable $e) {
            }

            return $this->success(null, 'Account and related data deleted successfully.');
        } catch (\Throwable $e) {
            return $this->error('Account deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/post-media-upload",
     *     summary="Upload media",
     *     description="Uploads media files",
     *     operationId="uploadMedia",
     *     tags={"Media"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function upload_media(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $rules = [
                'file' => 'required|file|max:10240', // 10MB max
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors());
            }

            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads', $filename, 'public');

            $url = asset('storage/' . $path);

            return $this->success([
                'url' => $url,
                'filename' => $filename,
                'path' => $path
            ], 'File uploaded successfully');
        } catch (Exception $e) {
            return $this->error('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/manifest",
     *     summary="Get app manifest",
     *     description="Retrieves application manifest data",
     *     operationId="getManifest",
     *     tags={"Utility"}
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
     *     summary="Get job seeker manifest",
     *     description="Retrieves job seeker specific manifest data",
     *     operationId="getJobSeekerManifest",
     *     tags={"Utility"}
     * )
     */
    public function job_seeker_manifest(Request $request)
    {
        try {
            $manifest = [
                'job_categories' => JobCategory::all(),
                'districts' => District::select(['id', 'name'])->get(),
                'skill_suggestions' => [
                    'PHP',
                    'Laravel',
                    'JavaScript',
                    'React',
                    'Vue.js',
                    'Node.js',
                    'Python',
                    'Django',
                    'Flutter',
                    'React Native',
                    'MySQL',
                    'PostgreSQL',
                    'MongoDB',
                    'AWS',
                    'Docker',
                    'Git'
                ],
                'education_levels' => [
                    'Primary',
                    'Secondary',
                    'Certificate',
                    'Diploma',
                    'Bachelor\'s Degree',
                    'Master\'s Degree',
                    'PhD'
                ],
                'experience_levels' => [
                    'Entry Level',
                    '1-2 years',
                    '3-5 years',
                    '5-10 years',
                    '10+ years'
                ]
            ];

            return $this->success($manifest, 'Job seeker manifest retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving job seeker manifest: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/view-record-create",
     *     summary="Create view record",
     *     description="Creates a view tracking record",
     *     operationId="createViewRecord",
     *     tags={"Analytics"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function view_record_create(Request $request)
    {
        try {
            $user = auth('api')->user();

            $rules = [
                'model' => 'required|string',
                'model_id' => 'required|integer',
                'type' => 'nullable|string'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error($validator->errors());
            }

            $viewRecord = ViewRecord::create([
                'user_id' => $user ? $user->id : null,
                'model' => $request->model,
                'model_id' => $request->model_id,
                'type' => $request->type ?? 'view',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()
            ]);

            return $this->success($viewRecord, 'View record created successfully');
        } catch (Exception $e) {
            return $this->error('Error creating view record: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/view-records",
     *     summary="Get view records",
     *     description="Retrieves view records for the authenticated user",
     *     operationId="getViewRecords",
     *     tags={"Analytics"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function view_records(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $viewRecords = ViewRecord::where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->limit(100)
                ->get();

            return $this->success($viewRecords, 'View records retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving view records: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/company-view-records",
     *     summary="Get company view records",
     *     description="Retrieves view records for company content",
     *     operationId="getCompanyViewRecords",
     *     tags={"Analytics"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function company_view_records(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $viewRecords = ViewRecord::where('user_id', $user->id)
                ->where('model', 'Job')
                ->orderBy('id', 'DESC')
                ->limit(100)
                ->get();

            return $this->success($viewRecords, 'Company view records retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving company view records: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/my-company-follows",
     *     summary="Get user's company follows",
     *     description="Retrieves companies followed by the authenticated user",
     *     operationId="getMyCompanyFollows",
     *     tags={"Company"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function my_company_follows(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $follows = CompanyFollow::with('company')
                ->where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->get();

            return $this->success($follows, 'Company follows retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving company follows: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/my-job-offers",
     *     summary="Get user's job offers",
     *     description="Retrieves job offers for the authenticated user",
     *     operationId="getMyJobOffers",
     *     tags={"Job"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function my_job_offers(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $offers = JobOffer::with(['job', 'company'])
                ->where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->get();

            return $this->success($offers, 'Job offers retrieved successfully');
        } catch (Exception $e) {
            return $this->error('Error retrieving job offers: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/job-offers/{id}",
     *     summary="Update job offer",
     *     description="Updates a job offer",
     *     operationId="updateJobOffer",
     *     tags={"Job"},
     *     security={{"apiAuth": {}}}
     * )
     */
    public function update_job_offer(Request $request, $id)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $offer = JobOffer::where('id', $id)
                ->where('company_id', $user->id)
                ->first();

            if (!$offer) {
                return $this->error('Job offer not found or unauthorized');
            }

            $updateData = $request->only([
                'status',
                'message',
                'salary',
                'start_date'
            ]);

            $offer->update($updateData);

            return $this->success($offer->fresh(), 'Job offer updated successfully');
        } catch (Exception $e) {
            return $this->error('Error updating job offer: ' . $e->getMessage());
        }
    }


    /**
     * Eight Learning API Methods
     */

    /**
     * Get course categories with course counts
     * Endpoint: GET /api/course-categories
     */
    public function course_categories(Request $r)
    {
        try {
            $categories = \App\Models\CourseCategory::active()
                ->ordered()
                ->get();

            return $this->success(
                $categories,
                'Course categories retrieved successfully.'
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve course categories: ' . $e->getMessage());
        }
    }

    /**
     * Get courses with optional filtering
     * Endpoint: GET /api/courses
     */
    public function courses(Request $r)
    {
        try {
            $query = \App\Models\Course::with(['category'])
                ->where('status', 'active');

            // Apply filters
            if ($r->has('category_id') && !empty($r->category_id)) {
                $query->where('category_id', $r->category_id);
            }

            if ($r->has('featured') && $r->featured == 'true') {
                $query->where('featured', 'Yes');
            }

            if ($r->has('search') && !empty($r->search)) {
                $search = $r->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('instructor_name', 'like', "%{$search}%");
                });
            }

            if ($r->has('difficulty_level') && !empty($r->difficulty_level)) {
                $query->where('difficulty_level', $r->difficulty_level);
            }

            if ($r->has('language') && !empty($r->language)) {
                $query->where('language', $r->language);
            }

            // Sorting
            $sort = $r->get('sort', 'created_at');
            $order = $r->get('order', 'desc');

            if ($sort === 'popular') {
                $query->orderBy('enrollment_count', 'desc');
            } elseif ($sort === 'rating') {
                $query->orderBy('rating_average', 'desc');
            } elseif ($sort === 'price') {
                $query->orderBy('price', $order);
            } else {
                $query->orderBy($sort, $order);
            }

            // Pagination
            $limit = min($r->get('limit', 20), 100); // Max 100 items
            $courses = $query->paginate($limit);

            return $this->success(
                $courses,
                'Courses retrieved successfully.'
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve courses: ' . $e->getMessage());
        }
    }

    /**
     * Get single course details
     * Endpoint: GET /api/courses/{id}
     */
    public function course_single(Request $r, $id)
    {
        try {
            $course = \App\Models\Course::with(['category'])
                ->where('status', 'active')
                ->findOrFail($id);

            // Get course units count
            $course->units_count = \App\Models\CourseUnit::where('course_id', $course->id)->count();

            // Get total materials count
            $course->materials_count = \App\Models\CourseMaterial::whereIn(
                'unit_id',
                \App\Models\CourseUnit::where('course_id', $course->id)->pluck('id')
            )->count();

            // Calculate total duration from units
            $totalDuration = \App\Models\CourseUnit::where('course_id', $course->id)
                ->sum('duration_minutes');
            $course->total_duration_minutes = $totalDuration;
            $course->total_duration_formatted = $this->formatDuration($totalDuration);

            // Check if current user is subscribed (if authenticated)
            $user = auth('api')->user();
            $course->is_subscribed = false;
            $course->subscription_status = '';

            if ($user) {
                $subscription = \App\Models\CourseSubscription::where([
                    'user_id' => $user->id,
                    'course_id' => $course->id
                ])->first();

                if ($subscription) {
                    $course->is_subscribed = true;
                    $course->subscription_status = $subscription->status;
                }
            }

            return $this->success(
                $course,
                'Course details retrieved successfully.'
            );
        } catch (Exception $e) {
            return $this->error('Course not found or failed to retrieve: ' . $e->getMessage());
        }
    }

    /**
     * Course subscription workflow
     * Endpoint: POST /api/course-subscribe
     */
    public function course_subscribe(Request $r)
    {
        $user = auth('api')->user();
        if ($user === null) {
            return $this->error('Authentication required.');
        }

        $validator = Validator::make($r->all(), [
            'course_id' => 'required|exists:courses,id',
            'payment_method' => 'required|string',
            'payment_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors());
        }

        try {
            // Check if already subscribed
            $existingSubscription = \App\Models\CourseSubscription::where([
                'user_id' => $user->id,
                'course_id' => $r->course_id
            ])->first();

            if ($existingSubscription) {
                return $this->error('You are already subscribed to this course. user id: ' . $user->id . " course id: " . $r->course_id);
            }

            // Get course details
            $course = \App\Models\Course::findOrFail($r->course_id);

            // Create subscription record
            $subscription = new \App\Models\CourseSubscription();
            $subscription->user_id = $user->id;
            $subscription->course_id = $r->course_id;
            $subscription->subscription_type = $course->price > 0 ? 'paid' : 'free';
            $subscription->status = $course->price > 0 ? 'pending' : 'active';
            $subscription->payment_method = $r->payment_method;
            $subscription->payment_amount = $r->payment_amount;
            $subscription->currency = $course->currency;
            $subscription->subscribed_at = now();

            if ($course->price == 0) {
                $subscription->payment_status = 'completed';
                $subscription->payment_date = now();
            } else {
                $subscription->payment_status = 'pending';
            }

            $subscription->save();

            // Create notification for admin
            $notification = new \App\Models\CourseNotification();
            $notification->user_id = $user->id;
            $notification->course_id = $r->course_id;
            $notification->type = 'enrollment';
            $notification->title = 'New Course Enrollment';
            $notification->message = "User {$user->name} has enrolled in course: {$course->title}";
            $notification->read_status = 'unread';
            $notification->save();

            return $this->success(
                $subscription,
                $course->price > 0
                    ? 'Course subscription submitted for approval. You will be notified once approved.'
                    : 'Successfully enrolled in the course!'
            );
        } catch (Exception $e) {
            return $this->error('Failed to subscribe to course: ' . $e->getMessage());
        }
    }

    /**
     * Get user's course subscriptions
     * Endpoint: GET /api/my-course-subscriptions
     */
    public function my_course_subscriptions(Request $r)
    {
        $user = auth('api')->user();
        if ($user === null) {
            return $this->error('Authentication required.');
        }

        try {
            $subscriptions = \App\Models\CourseSubscription::with(['course.category'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success(
                $subscriptions,
                'Course subscriptions retrieved successfully.'
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve subscriptions: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to format duration
     */
    private function formatDuration($minutes)
    {
        if (!$minutes) return '0 min';

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
        }

        return $mins . 'm';
    }
}
