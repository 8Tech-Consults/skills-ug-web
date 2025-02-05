<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminRoleUser;
use App\Models\Consultation;
use App\Models\District;
use App\Models\DoseItem;
use App\Models\DoseItemRecord;
use App\Models\FlutterWaveLog;
use App\Models\Image;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobCategory;
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
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
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
            'cvs',
        ]]);
    }


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


        $except = ['id', 'applicant_id', 'status', 'slug'];
        try {
            $jobAppication = Utils::fetch_post($jobAppication, $except, $r->all());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
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
     *     path="/users/profile/update",
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
     * @OA\Get(
     *     path="/users/me",
     *     summary="Get current user profile",
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
        return $this->success($query, $message = "Profile details", 200);
    }


    public function job_single(Request $request)
    {
        $job = Job::find($request->id);

        if (!$job) {
            return $this->error('Job not found. => #' . $request->id);
        }
        return $this->success($job, 'Job retrieved successfully.');
    }


    public function jobs(Request $request)
    {

        // Start building query
        $query = Job::where('status', 'Active');

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
        $perPage = $request->input('per_page', 16);
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
        $TOP_CITIES = District::select('id', 'name', 'jobs_count', 'photo')
            ->orderBy('jobs_count', 'DESC')
            ->limit(10)
            ->get();
        $CATEGORIES = JobCategory::all();
        //Latest 50 jobs
        $TOP_JOBS = Job::where('status', 'Active')
            ->orderBy('id', 'DESC')
            ->limit(50)
            ->get();
        $manifest = [
            'LIVE_JOBS' => number_format(Job::where('status', 'Active')->count()),
            'VACANCIES' => number_format(Job::where('status', 'Active')->sum('vacancies_count')),
            'COMPANIES' => number_format(User::count()),
            'NEW_JOBS' => number_format(Job::where('status', 'Active')->where('created_at', '>=', Carbon::now()->subDays(7))->count()),
            'TOP_CITIES' => $TOP_CITIES,
            'CATEGORIES' => $CATEGORIES,
            'TOP_JOBS' => $TOP_JOBS,
        ];

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

        $new_user = Administrator::find($user->id);
        if ($new_user == null) {
            return $this->error('Account created successfully but failed to log you in.');
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
}
