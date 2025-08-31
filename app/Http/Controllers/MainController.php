<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\District;
use App\Models\Event;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\NewsPost;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller as BaseController;

class MainController extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiResponser;


  /**
   * @OA\Post(
   *     path="/send-mail-verification-code",
   *     summary="Send mail verification code",
   *     description="Sends a mail verification code to the authenticated user's email address.",
   *     operationId="sendMailVerificationCode",
   *     tags={"Authentication"},
   *     security={{ "apiAuth": {} }},
   *     @OA\Response(
   *         response=200,
   *         description="Verification code sent successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Verification code sent successfully."),
   *             @OA\Property(property="data", type="null", example=null)
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthorized - User not found",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="User not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=500,
   *         description="Failed to send verification code",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Verification code sending failed.")
   *         )
   *     )
   * )
   */
  public function send_mail_verification_code(Request $request)
  {
    $user = auth('api')->user();
    if (!$user) {
      return $this->error('User not found.');
    }
    $user = User::find($user->id);
    if (!$user) {
      return $this->error('User not found.');
    }
    try {
      $user->send_mail_verification_code();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
    return $this->success(null, 'Verification code sent successfully.');
  }


  


  /**
   * @OA\Post(
   *     path="/password-reset-submit",
   *     summary="Submit password reset",
   *     description="Allows a user to reset their password by providing the email, verification code, and new password.",
   *     operationId="passwordResetSubmit",
   *     tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         description="Password reset submission data",
   *         @OA\JsonContent(
   *             required={"email", "code", "password"},
   *             @OA\Property(
   *                 property="email",
   *                 type="string",
   *                 format="email",
   *                 example="user@example.com",
   *                 description="The email address associated with the user's account"
   *             ),
   *             @OA\Property(
   *                 property="code",
   *                 type="string",
   *                 example="123456",
   *                 description="The verification code sent to the user's email"
   *             ),
   *             @OA\Property(
   *                 property="password",
   *                 type="string",
   *                 format="password",
   *                 example="NewSecurePassword123",
   *                 description="The new password (must be at least 6 characters)"
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Password reset successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Password reset successfully. You can now login with your new password."),
   *             @OA\Property(property="data", type="null", example=null)
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Bad Request - Missing or invalid input",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Email, code, and password are required.")
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
   *             @OA\Property(property="message", type="string", example="An error occurred while resetting the password.")
   *         )
   *     )
   * )
   */
  public function password_reset_submit(Request $request)
  {
    $email = $request->email;
    $code = $request->code;
    $password = $request->password;

    if (!$email || !$code || !$password) {
      return $this->error('Email, code, and password are required.');
    }

    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $this->error('Invalid email address.');
    }

    $user = User::where('email', $email)->first();
    if (!$user) {
      return $this->error('User not found.');
    }

    if ($user->code !== $code) {
      return $this->error('Verification code is incorrect.');
    }

    if (strlen($password) < 4) {
      return $this->error('Password must be at least 4 characters.');
    }

    $user->password = password_hash($password, PASSWORD_DEFAULT);
    $user->code = null;
    $user->code_sent_at = null;
    $user->code_is_sent = null;

    try {
      $user->save();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }

    return $this->success(null, 'Password reset successfully. You can now login with your new password.');
  }




  /**
   * @OA\Post(
   *     path="/password-reset-request",
   *     summary="Request password reset",
   *     description="Sends a password reset code to the user's email address.",
   *     operationId="passwordResetRequest",
   *     tags={"Authentication"},
   *     @OA\RequestBody(
   *         required=true,
   *         description="Password reset request data",
   *         @OA\JsonContent(
   *             required={"email"},
   *             @OA\Property(
   *                 property="email",
   *                 type="string",
   *                 format="email",
   *                 example="user@example.com",
   *                 description="The email address associated with the user's account"
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Password reset code sent successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Password reset code sent successfully."),
   *             @OA\Property(property="data", type="null", example=null)
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Bad Request - Missing or invalid input",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Email is required.")
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
   *             @OA\Property(property="message", type="string", example="An error occurred while sending the password reset code.")
   *         )
   *     )
   * )
   */
  public function password_reset_request(Request $request)
  {
    $email = $request->email;
    if ($email == null) {
      return $this->error('Email is required.');
    }
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $this->error('Invalid email address.');
    }
    $user = User::where('email', $email)->first();
    if ($user == null) {
      return $this->error('User not found.');
    }
    try {
      $user->password_reset_request();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
    return $this->success(null, 'Password reset code sent successfully.');
  }

  /**
   * @OA\Post(
   *     path="/account-deletion-request",
   *     summary="Request account deletion code",
   *     description="Sends a verification code to the user's email for account deletion confirmation.",
   *     operationId="accountDeletionRequest",
   *     tags={"User Authentication"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Account deletion code sent successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Account deletion code sent to your email.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthorized",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Unauthorized access.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=429,
   *         description="Too many requests",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Please wait before requesting another code.")
   *         )
   *     )
   * )
   */
  public function account_deletion_request(Request $request)
  {
    $user = auth('api')->user();
    if (!$user) {
      return $this->error('Unauthorized access.');
    }

    try {
      $user->account_deletion_request();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
    
    return $this->success(null, 'Account deletion code sent to your email.');
  }

  /**
   * @OA\Post(
   *     path="/account-deletion-confirm",
   *     summary="Confirm account deletion",
   *     description="Confirms account deletion using verification code and password.",
   *     operationId="accountDeletionConfirm",
   *     tags={"User Authentication"},
   *     security={{"bearerAuth": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             type="object",
   *             required={"verification_code", "password", "confirmation_text"},
   *             @OA\Property(property="verification_code", type="string", example="123456", description="6-digit verification code"),
   *             @OA\Property(property="password", type="string", example="currentpassword", description="Current user password"),
   *             @OA\Property(property="confirmation_text", type="string", example="DELETE MY ACCOUNT", description="Confirmation text")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Account deleted successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Account deleted successfully.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid input",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Invalid verification code or password.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthorized",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Unauthorized access.")
   *         )
   *     )
   * )
   */
  public function account_deletion_confirm(Request $request)
  {
    $user = auth('api')->user();
    if (!$user) {
      return $this->error('Unauthorized access.');
    }

    $verification_code = $request->verification_code;
    $password = $request->password;
    $confirmation_text = $request->confirmation_text;

    // Validate required fields
    if (empty($verification_code)) {
      return $this->error('Verification code is required.');
    }
    if (empty($password)) {
      return $this->error('Password is required.');
    }
    if (empty($confirmation_text)) {
      return $this->error('Confirmation text is required.');
    }

    // Validate confirmation text
    if (trim($confirmation_text) !== 'DELETE MY ACCOUNT') {
      return $this->error('Invalid confirmation text. Please type exactly: DELETE MY ACCOUNT');
    }

    // Validate verification code
    if ($user->code != $verification_code) {
      return $this->error('Invalid verification code.');
    }

    // Check code expiry (10 minutes)
    if ($user->code_sent_at && Carbon::parse($user->code_sent_at)->addMinutes(10) < Carbon::now()) {
      return $this->error('Verification code has expired. Please request a new one.');
    }

    // Validate password
    if (!Hash::check($password, $user->password)) {
      return $this->error('Invalid password.');
    }

    try {
      // Delete user account and related data
      $user->delete_account();
    } catch (\Throwable $th) {
      return $this->error('Failed to delete account: ' . $th->getMessage());
    }

    return $this->success(null, 'Account deleted successfully.');
  }

  /**
   * @OA\Post(
   *     path="/contact-form-submit",
   *     summary="Submit contact form",
   *     description="Submit a contact form with user inquiry details.",
   *     operationId="contactFormSubmit",
   *     tags={"Contact"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             type="object",
   *             required={"name", "email", "subject", "message", "inquiry_type"},
   *             @OA\Property(property="name", type="string", example="John Doe", description="Full name of the person"),
   *             @OA\Property(property="email", type="string", example="john@example.com", description="Email address"),
   *             @OA\Property(property="phone", type="string", example="+256700000000", description="Phone number (optional)"),
   *             @OA\Property(property="subject", type="string", example="Job Application Help", description="Subject of the inquiry"),
   *             @OA\Property(property="message", type="string", example="I need help with my job application", description="Detailed message"),
   *             @OA\Property(property="inquiry_type", type="string", example="General Inquiry", description="Type of inquiry"),
   *             @OA\Property(property="company", type="string", example="Tech Company", description="Company name (optional)")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Contact form submitted successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Contact form submitted successfully. We will respond within 24 hours.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid input",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Please provide all required fields.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=429,
   *         description="Too many requests",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Please wait before submitting another message.")
   *         )
   *     )
   * )
   */
  public function contact_form_submit(Request $request)
  {
    $name = trim($request->name);
    $email = trim($request->email);
    $phone = trim($request->phone);
    $subject = trim($request->subject);
    $message = trim($request->message);
    $inquiry_type = trim($request->inquiry_type);
    $company = trim($request->company);

    // Validate required fields
    if (empty($name)) {
      return $this->error('Name is required.');
    }
    if (empty($email)) {
      return $this->error('Email is required.');
    }
    if (empty($subject)) {
      return $this->error('Subject is required.');
    }
    if (empty($message)) {
      return $this->error('Message is required.');
    }
    if (empty($inquiry_type)) {
      return $this->error('Inquiry type is required.');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $this->error('Invalid email address.');
    }

    // Rate limiting: Check if same email submitted in the last 5 minutes
    $recent_submission = DB::table('contact_submissions')
      ->where('email', $email)
      ->where('created_at', '>', Carbon::now()->subMinutes(5))
      ->first();

    if ($recent_submission) {
      return $this->error('Please wait 5 minutes before submitting another message.');
    }

    try {
      // Store the contact submission in database
      $submission_id = DB::table('contact_submissions')->insertGetId([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message,
        'inquiry_type' => $inquiry_type,
        'company' => $company,
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'status' => 'pending',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
      ]);

      // Send notification email to support team
      $this->send_contact_form_notification($submission_id, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message,
        'inquiry_type' => $inquiry_type,
        'company' => $company,
      ]);

      // Send confirmation email to user
      $this->send_contact_form_confirmation($email, $name, $subject);

    } catch (\Throwable $th) {
      return $this->error('Failed to submit contact form: ' . $th->getMessage());
    }

    return $this->success(null, 'Contact form submitted successfully. We will respond within 24 hours.');
  }

  private function send_contact_form_notification($submission_id, $data)
  {
    try {
      $support_email = env('SUPPORT_EMAIL', 'support@skillsug.com');
      
      $email_data = [
        'email' => $support_email,
        'name' => 'Support Team',
        'subject' => 'New Contact Form Submission: ' . $data['subject'],
      ];

      $body = <<<EOF
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #114786; border-bottom: 2px solid #114786; padding-bottom: 10px;">
          New Contact Form Submission
        </h2>
        
        <div style="background-color: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">
          <h3 style="margin-top: 0; color: #333;">Submission Details</h3>
          <p><strong>Submission ID:</strong> #{$submission_id}</p>
          <p><strong>Date:</strong> {date('Y-m-d H:i:s')}</p>
          <p><strong>Inquiry Type:</strong> {$data['inquiry_type']}</p>
        </div>

        <div style="background-color: #ffffff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
          <h3 style="margin-top: 0; color: #333;">Contact Information</h3>
          <p><strong>Name:</strong> {$data['name']}</p>
          <p><strong>Email:</strong> {$data['email']}</p>
          <p><strong>Phone:</strong> {$data['phone']}</p>
          <p><strong>Company:</strong> {$data['company']}</p>
        </div>

        <div style="background-color: #ffffff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
          <h3 style="margin-top: 0; color: #333;">Subject</h3>
          <p style="font-weight: bold; color: #114786;">{$data['subject']}</p>
          
          <h3 style="color: #333;">Message</h3>
          <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #114786; white-space: pre-wrap;">{$data['message']}</div>
        </div>

        <div style="margin-top: 30px; padding: 20px; background-color: #e6f7ff; border-radius: 5px;">
          <p style="margin: 0; color: #666; font-size: 14px;">
            Please respond to this inquiry within 24 hours. You can reply directly to the customer at: {$data['email']}
          </p>
        </div>
      </div>
      EOF;

      $email_data['body'] = $body;
      $email_data['view'] = 'mail-1';
      $email_data['data'] = $body;

      Utils::mail_sender($email_data);
    } catch (\Throwable $th) {
      // Log error but don't fail the main request
      error_log('Failed to send contact form notification: ' . $th->getMessage());
    }
  }

  private function send_contact_form_confirmation($email, $name, $subject)
  {
    try {
      $email_data = [
        'email' => $email,
        'name' => $name,
        'subject' => env('APP_NAME') . ' - Contact Form Received: ' . $subject,
      ];

      $body = <<<EOF
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #114786; border-bottom: 2px solid #114786; padding-bottom: 10px;">
          Thank You for Contacting Us
        </h2>
        
        <p style="font-size: 16px; line-height: 1.6; color: #555;">
          Dear {$name},
        </p>
        
        <p style="font-size: 16px; line-height: 1.6; color: #555;">
          Thank you for reaching out to us through our contact form. We have successfully received your message regarding "<strong>{$subject}</strong>".
        </p>

        <div style="background-color: #e6f7ff; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #114786;">
          <h3 style="margin-top: 0; color: #114786;">What happens next?</h3>
          <ul style="color: #555; line-height: 1.6;">
            <li>Our support team will review your message within 2-4 hours</li>
            <li>You will receive a detailed response within 24 hours</li>
            <li>For urgent matters, you can call us directly at +256 700 000 000</li>
          </ul>
        </div>

        <div style="background-color: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">
          <h3 style="margin-top: 0; color: #333;">Need immediate assistance?</h3>
          <p style="color: #555; margin-bottom: 10px;">You can also reach us through:</p>
          <p style="color: #555; margin: 5px 0;"><strong>Email:</strong> support@skillsug.com</p>
          <p style="color: #555; margin: 5px 0;"><strong>Phone:</strong> +256 700 000 000</p>
          <p style="color: #555; margin: 5px 0;"><strong>WhatsApp:</strong> +256 700 000 000</p>
        </div>

        <p style="font-size: 16px; line-height: 1.6; color: #555;">
          Thank you for choosing 8Jobspot!
        </p>

        <hr style="border: 1px solid #e0e0e0; margin: 30px 0;">
        <p style="font-size: 14px; color: #999; text-align: center;">
          This is an automated confirmation. Please do not reply to this email.
        </p>
      </div>
      EOF;

      $email_data['body'] = $body;
      $email_data['view'] = 'mail-1';
      $email_data['data'] = $body;

      Utils::mail_sender($email_data);
    } catch (\Throwable $th) {
      // Log error but don't fail the main request
      error_log('Failed to send contact form confirmation: ' . $th->getMessage());
    }
  }




  /**
   * @OA\Get(
   *     path="/jobs/{id}",
   *     summary="Get details of a single job",
   *     description="Retrieves detailed information about a specific job using its ID.",
   *     operationId="getJobById",
   *     tags={"Job"},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         description="ID of the job to retrieve",
   *         required=true,
   *         @OA\Schema(type="integer", example=10)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Job retrieved successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Job retrieved successfully."),
   *             @OA\Property(
   *                 property="data",
   *                 type="object",
   *                 @OA\Property(property="id", type="integer", example=10),
   *                 @OA\Property(property="title", type="string", example="Software Engineer"),
   *                 @OA\Property(property="status", type="string", example="active"),
   *                 @OA\Property(property="category_id", type="integer", example=5),
   *                 @OA\Property(property="district_id", type="integer", example=10),
   *                 @OA\Property(property="employment_status", type="string", example="Full Time"),
   *                 @OA\Property(property="workplace", type="string", example="Onsite"),
   *                 @OA\Property(property="minimum_salary", type="number", format="float", example=50000),
   *                 @OA\Property(property="maximum_salary", type="number", format="float", example=70000),
   *                 @OA\Property(property="posted_by_id", type="integer", example=2),
   *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
   *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T12:00:00Z")
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Job not found",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Job not found. => #10")
   *         )
   *     ),
   *     @OA\Response(
   *         response=500,
   *         description="Internal Server Error",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="An error occurred while retrieving job details.")
   *         )
   *     )
   * )
   */

  public function job_single(Request $request)
  {
    $job = Job::find($request->id);

    if (!$job) {
      return $this->error('Job not found. => #' . $request->id);
    }
    return $this->success($job, 'Job retrieved successfully.');
  }



  /**
   * @OA\Get(
   *     path="/cvs/{id}",
   *     summary="Get details of a user's CV",
   *     description="Retrieves detailed information of a user's CV using the provided ID.",
   *     operationId="getCvById",
   *     tags={"CV"},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         description="ID of the user profile to retrieve CV for",
   *         required=true,
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="CV retrieved successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="message", type="string", example="Profile details"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Account not found",
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
   *             @OA\Property(property="message", type="string", example="An error occurred while retrieving CV details.")
   *         )
   *     )
   * )
   */
  public function cv_single(Request $request)
  {


    $u = User::find($request->id);
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
      try {
        User::save_cv($u);
      } catch (\Throwable $th) {
        return $this->error($th->getMessage());
      }
      $u = User::find($u->id);
    }

    return $this->success($u, $message = "Profile details", 200);
  }

  public function index()
  {

    /*     die("<h1>Something really cool is coming soon! 🥰</h1>"); */
    $members = Administrator::where([])->orderBy('updated_at', 'desc')->limit(8)->get();
    $profiles = [];
    $_profiles = [];
    foreach (Administrator::where([])->orderBy('updated_at', 'desc')->limit(15)->get() as $key => $v) {
      $profiles[] = $v;
    }

    foreach ($profiles as $key => $pro) {
      if ($pro->intro == null || strlen($pro->intro) < 3) {
        $pro->intro = "Hi there, I'm $pro->name . I call upon you to join the team!";
      }
      $_profiles[] = $pro;
    }

    $posts = [];
    foreach (NewsPost::all() as $key => $v) {
      $posts[] = $v;
    }
    shuffle($posts);
    $_posts = [];
    $i = 0;
    foreach ($posts as $key => $v) {
      $_posts[] = $v;
      $i++;
      if ($i > 2) {
        break;
      }
    }

    return view('index', [
      'members' => $members,
      'profiles' => $_profiles,
      'posts' => $_posts,
    ]);
  }
  public function about_us()
  {
    return view('about-us');
  }
  public function our_team()
  {
    return view('our-team');
  }
  public function news_category()
  {
    return view('news-category');
  }

  public function dinner()
  {
    $p = Event::find(1);
    if ($p == null) {
      die("Post not found.");
    }
    return view('dinner', [
      'd' => $p
    ]);
  }

  public function news(Request $r)
  {
    $p = NewsPost::find($r->id);
    if ($p == null) {
      die("Post not found.");
    }

    $posts = [];
    foreach (NewsPost::all() as $key => $v) {
      $posts[] = $v;
    }
    shuffle($posts);
    $_posts = [];
    $i = 0;
    foreach ($posts as $key => $v) {
      $_posts[] = $v;
      $i++;
      if ($i > 2) {
        break;
      }
    }

    return view('news-post', [
      'p' => $p,
      'post' => $p,
      'posts' => $_posts,
    ]);
  }
  public function members()
  {
    $members = Administrator::where([])->orderBy('id', 'desc')->limit(12)->get();
    return view('members', [
      'members' => $members
    ]);
  }

  function generate_class()
  {

    $data = 'id, created_at, updated_at, garden_id, user_id, crop_activity_id, activity_name, activity_description, activity_date_to_be_done, activity_due_date, activity_date_done, farmer_has_submitted, farmer_activity_status, farmer_submission_date, farmer_comment, agent_id, agent_names, agent_has_submitted, agent_activity_status, agent_comment, agent_submission_date';

    $modelName = 'GardenActivity';
    $endPoint = 'garden-activities';
    $tableName = 'garden_activities';
    //$array = preg_split('/\r\n|\n\r|\r|\n/', $data);
    $array = explode(',', $data);
    $generate_vars = MainController::generate_vars($array);
    $fromJson = MainController::fromJson($array);
    $from_json = MainController::from_json($array);
    $toJson = MainController::to_json($array);
    $create_table = MainController::create_table($array, $modelName);
    return <<<EOT
<pre>
import 'package:marcci/utils/Utils.dart';
import 'package:sqflite/sqflite.dart';

import 'RespondModel.dart';

class $modelName {
  static String endPoint = "$endPoint";
  static String tableName = "$tableName";

  $generate_vars

  static fromJson(dynamic m) {
    $modelName obj = new $modelName();
    if (m == null) {
      return obj;
    }
    
  $fromJson
  return obj;
}

  


  static Future&lt;List&lt;$modelName&gt;&gt; getLocalData({String where: "1"}) async {
    List&lt;$modelName&gt; data = [];
    if (!(await $modelName.initTable())) {
      Utils.toast("Failed to init dynamic store.");
      return data;
    }

    Database db = await Utils.dbInit();
    if (!db.isOpen) {
      return data;
    }

    List&lt;Map&gt; maps = await db.query($modelName.tableName, where: where);

    if (maps.isEmpty) {
      return data;
    }
    List.generate(maps.length, (i) {
      data.add($modelName.fromJson(maps[i]));
    });

    return data;
  }


  static Future&lt;List&lt;$modelName&gt;&gt; getItems({String where = '1'}) async {
    List&lt;$modelName&gt; data = await getLocalData(where: where);
    if (data.isEmpty) {
      await $modelName.getOnlineItems();
      data = await getLocalData(where: where);
    } else {
      data = await getLocalData(where: where);
      $modelName.getOnlineItems();
    }
    data.sort((a, b) => b.id.compareTo(a.id));
    return data;
  }

  static Future&lt;List&lt;$modelName&gt;&gt; getOnlineItems() async {
    List&lt;$modelName&gt; data = [];

    RespondModel resp =
        RespondModel(await Utils.http_get($modelName.endPoint, {}));

    if (resp.code != 1) {
      return [];
    }

    Database db = await Utils.dbInit();
    if (!db.isOpen) {
      Utils.toast("Failed to init local store.");
      return [];
    }

    if (resp.data.runtimeType.toString().contains('List')) {
      if (await Utils.is_connected()) {
        await $modelName.deleteAll();
      }

      await db.transaction((txn) async {
        var batch = txn.batch();

        for (var x in resp.data) {
          $modelName sub = $modelName.fromJson(x);
          try {
            batch.insert(tableName, sub.toJson(),
                conflictAlgorithm: ConflictAlgorithm.replace);
          } catch (e) {}
        }

        try {
          await batch.commit(continueOnError: true);
        } catch (e) {}
      });
    }

    return [];

    return data;
  }

  save() async {
    Database db = await Utils.dbInit();
    if (!db.isOpen) {
      Utils.toast("Failed to init local store.");
      return;
    }

    await initTable();

    try {
      await db.insert(
        tableName,
        toJson(),
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    } catch (e) {
      Utils.toast("Failed to save student because \${e.toString()}");
    }
  }

  toJson() {
    return {
     $toJson
    };
  }

  static Future&lt;bool&gt; initTable() async {
    Database db = await Utils.dbInit();
    if (!db.isOpen) {
      return false;
    }

    String sql = "$create_table";

    try {
      //await db.delete(tableName);

      await db.execute(sql);
    } catch (e) {
      Utils.log('Failed to create table because \${e.toString()}');

      return false;
    }

    return true;
  }

  static deleteAll() async {
    if (!(await $modelName.initTable())) {
      return;
    }
    Database db = await Utils.dbInit();
    if (!db.isOpen) {
      return false;
    }
    await db.delete(tableName);
  }
}
</pre>
EOT;

    return view('generate-class', [
      'modelName' => $modelName,
      'endPoint' => $endPoint,
      'fromJson' => MainController::fromJson($vars),
    ]);
  }

  function generate_variables($data)
  {

    MainController::createNew($recs);
    MainController::from_json($recs);
    MainController::fromJson($recs);
    MainController::generate_vars($recs);
    MainController::create_table($recs, 'people');
    //MainController::to_json($recs);
  }


  function createNew($recs)
  {

    $_data = "";

    foreach ($recs as $v) {
      $key = trim($v);

      $_data .= "\$obj->{$key} =  \$r->{$key};<br>";
    }

    return $_data;
  }


  function fromJson($recs)
  {

    $_data = "";

    foreach ($recs as $v) {
      $key = trim($v);
      if (strlen($key) < 1) {
        continue;
      }
      if ($key == 'id') {
        $_data .= "obj.{$key} = Utils.int_parse(m['{$key}']);<br>";
      } else {
        $_data .= "obj.{$key} = Utils.to_str(m['{$key}'],'');<br>";
      }
    }
    return $_data;
  }



  function create_table($recs, $modelName)
  {

    $__t = '${' . $modelName . '.tableName}';
    $_data = "CREATE TABLE  IF NOT EXISTS  $__t (  " . '"';
    $i = 0;
    $len = count($recs);
    foreach ($recs as $v) {
      $key = trim($v);
      $i++;
      if (strlen($key) < 1) {
        continue;
      }

      $_data .= '<br>"';
      if ($key == 'id') {
        $_data .= 'id INTEGER PRIMARY KEY';
      } else {
        '"' . $_data .= " $key TEXT";
      }


      if ($i  != $len) {
        $_data .= ',"';
      }
    }

    $_data .= ')';
    return $_data;
  }


  function from_json($recs)
  {

    $_data = "";
    foreach ($recs as $v) {

      $key = trim($v);
      if (strlen($key) < 2) {
        continue;
      }
      $_data .= "$key : $key,<br>";
    }

    return $_data;
  }


  function to_json($recs)
  {
    $_data = "";
    foreach ($recs as $v) {
      $key = trim($v);
      if (strlen($key) < 2) {
        continue;
      }
      $_data .= "'$key' : $key,<br>";
    }

    return $_data;
  }

  function generate_vars($recs)
  {

    $_data = "";
    foreach ($recs as $v) {
      $key = trim($v);
      if (strlen($key) < 1) {
        continue;
      }

      if ($key == 'id') {
        $_data .= "int $key = 0;<br>";
      } else {
        $_data .= "String $key = \"\";<br>";
      }
    }

    return $_data;
  }

  function gen_jobs()
  {

    $functional_cats_ids = JobCategory::where([
      'type' => 'Functional'
    ])->pluck('id')->toArray();
    $industry_cats_ids = JobCategory::where([
      'type' => 'Industry'
    ])->pluck('id')->toArray();

    // A pool of 200 functional job titles
    $functionalTitles = [
      "Innovative Software Engineering Specialist",
      "Certified Public Accountant and Financial Expert",
      "Dynamic Marketing Strategy Manager",
      "Human Resources and Organizational Development Specialist",
      "Expert Financial Analysis Consultant",
      "Strategic Business Analysis Professional",
      "Advanced Data Science and Analytics Expert",
      "High-Performing Sales Executive",
      "Experienced Project Management Leader",
      "Creative Graphic Design Specialist",
      "Customer Service Excellence Representative",
      "Product Management and Development Specialist",
      "Talent Acquisition and Recruitment Officer",
      "Efficient Administrative Support Assistant",
      "IT Support and Systems Specialist",
      "Operations Management and Optimization Expert",
      "Compliance and Regulatory Affairs Officer",
      "Corporate Training and Development Coordinator",
      "Public Relations and Communications Officer",
      "Digital Marketing and Online Presence Specialist",
      "User Experience (UX) and User Interface (UI) Designer",
      "Technical Writing and Documentation Specialist",
      "Quality Assurance and Testing Analyst",
      "Social Media Strategy and Management Specialist",
      "Network Administration and Security Specialist",
      "Database Administration and Management Expert",
      "Cloud Engineering and Infrastructure Specialist",
      "Cybersecurity and Threat Analysis Expert",
      "Search Engine Optimization (SEO) Specialist",
      "Legal Consultancy and Advisory Expert",
      "Risk Management and Mitigation Specialist",
      "Certified Financial Planning Advisor",
      "Investment Analysis and Portfolio Management Specialist",
      "Bank Teller and Customer Service Representative",
      "Mortgage Advisory and Loan Consultant",
      "Professional Actuary and Risk Analyst",
      "Certified Auditor and Financial Compliance Expert",
      "Supply Chain Analysis and Logistics Specialist",
      "Procurement Management and Sourcing Expert",
      "E-commerce Management and Online Sales Specialist",
      "Content Strategy and Development Specialist",
      "Market Research and Consumer Insights Analyst",
      "Brand Management and Development Specialist",
      "Advertising and Media Planning Specialist",
      "Corporate Communications and Public Affairs Manager",
      "Community Engagement and Outreach Manager",
      "Fundraising and Development Manager",
      "Event Planning and Coordination Specialist",
      "Travel Consultancy and Planning Expert",
      "Training Coordination and Program Development Specialist",
      "Human Resources Management and Policy Expert",
      "Employee Relations and Conflict Resolution Specialist",
      "Compensation and Benefits Management Specialist",
      "Payroll Processing and Management Specialist",
      "Labor Relations and Employment Law Consultant",
      "Corporate Legal Advisor and Consultant",
      "Intellectual Property Law Specialist",
      "Certified Paralegal and Legal Assistant",
      "Contract Management and Negotiation Specialist",
      "Policy Analysis and Development Specialist",
      "Government Relations and Advocacy Specialist",
      "Economic Analysis and Policy Advisor",
      "Urban Planning and Development Specialist",
      "Business Development and Growth Executive",
      "Retail Management and Operations Specialist",
      "Franchise Management and Development Specialist",
      "Customer Insights and Data Analysis Specialist",
      "Customer Retention and Loyalty Specialist",
      "Public Health Administration and Policy Expert",
      "Medical Administration and Healthcare Management Specialist",
      "Healthcare Consultancy and Advisory Expert",
      "Clinical Data Management and Analysis Specialist",
      "Biostatistics and Data Analysis Expert",
      "Pharmaceutical Sales and Marketing Representative",
      "Medical Science Liaison and Advisor",
      "Hospital Administration and Management Specialist",
      "Insurance Underwriting and Risk Assessment Specialist",
      "Claims Adjustment and Processing Specialist",
      "Human Resources Information Systems (HRIS) Analyst",
      "Linguistics and Language Analysis Specialist",
      "Foreign Affairs and International Relations Analyst",
      "Trade Compliance and Regulatory Affairs Officer",
      "Real Estate Sales and Property Management Agent",
      "Property Management and Facilities Coordinator",
      "Media Planning and Advertising Specialist",
      "Video Production and Content Management Specialist",
      "Podcast Production and Management Specialist",
      "Film Editing and Post-Production Specialist",
      "Digital Content Creation and Strategy Specialist",
      "Virtual Assistance and Administrative Support Specialist",
      "Technical Recruitment and Talent Acquisition Specialist",
      "Product Ownership and Agile Management Specialist",
      "Certified Scrum Master and Agile Coach",
      "Artificial Intelligence (AI) Engineering Specialist",
      "Blockchain Development and Cryptography Expert",
      "Machine Learning Engineering and Data Science Specialist",
      "Augmented Reality (AR) and Virtual Reality (VR) Developer",
      "Game Development and Interactive Media Specialist",
      "Software Testing and Quality Assurance Specialist",
      "IT Project Coordination and Management Specialist",
      "Network Security Engineering and Cyber Defense Specialist",
      "Data Engineering and Big Data Specialist",
      "Information Systems Management and Strategy Expert",
      "E-learning Development and Educational Technology Specialist",
      "Educational Technology (EdTech) Specialist",
      "Nonprofit Program Management and Development Specialist",
      "Public Policy Advocacy and Analysis Specialist",
      "Cultural Affairs and International Relations Officer",
      "Market Intelligence and Competitive Analysis Specialist",
      "Chief Marketing Officer (CMO) and Brand Strategist",
      "Chief Technology Officer (CTO) and Innovation Leader",
      "Chief Financial Officer (CFO) and Financial Strategist",
      "Chief Operating Officer (COO) and Operations Leader",
      "Chief Human Resources Officer (CHRO) and Talent Strategist",
      "Corporate Sustainability and Environmental Management Specialist",
      "Corporate Social Responsibility (CSR) Specialist",
      "Legal Compliance and Regulatory Affairs Officer",
      "Environmental Consultancy and Sustainability Expert",
      "Renewable Energy Analysis and Policy Specialist",
      "Energy Policy Analysis and Development Specialist",
      "Telehealth Coordination and Management Specialist",
      "Healthcare Data Analysis and Informatics Specialist",
      "Bioinformatics and Genomics Research Specialist",
      "Genomics Research and Data Analysis Specialist",
      "Regulatory Affairs and Compliance Specialist",
      "Clinical Research and Development Associate",
      "Mental Health Counseling and Therapy Specialist",
      "Crisis Intervention and Support Specialist",
      "Substance Abuse Counseling and Rehabilitation Specialist",
      "Veterinary Consultancy and Animal Health Specialist",
      "Pharmaceutical Research and Development Scientist",
      "Biomedical Engineering and Medical Device Specialist",
      "Speech Therapy and Communication Specialist",
      "Nutrition and Dietetics Specialist",
      "Personal Training and Fitness Coaching Specialist",
      "Sports Analysis and Performance Specialist",
      "Music Production and Sound Engineering Specialist",
      "Art Direction and Creative Design Specialist",
      "Museum Curation and Exhibition Specialist",
      "Archival Management and Historical Research Specialist",
      "Library Science and Information Management Specialist",
      "Historical Research and Documentation Specialist",
      "Theater Direction and Production Specialist",
      "Fashion Design and Creative Direction Specialist",
      "Interior Design and Space Planning Specialist",
      "Illustration and Visual Storytelling Specialist",
      "Professional Tattoo Artistry and Design Specialist",
      "Animation and Motion Graphics Specialist",
      "Game Testing and Quality Assurance Specialist",
      "Cybersecurity Consultancy and Risk Management Specialist",
      "Ethical Hacking and Penetration Testing Specialist",
      "Forensic Analysis and Cyber Investigation Specialist",
      "Drone Operation and Aerial Photography Specialist",
      "IT Auditing and Compliance Specialist",
      "Cloud Security Engineering and Infrastructure Specialist",
      "DevOps Engineering and Continuous Integration Specialist",
      "Enterprise Resource Planning (ERP) Consultancy Specialist",
      "Geographic Information Systems (GIS) Analysis Specialist",
      "Internet of Things (IoT) Engineering Specialist",
      "Metaverse Consultancy and Virtual World Specialist",
      "Quantum Computing Research and Development Specialist",
      "Legal Technology (Legal Tech) Consultancy Specialist",
      "Human Resources Business Partner and Strategy Specialist",
      "Customer Experience Management and Strategy Specialist",
      "Employee Engagement and Retention Specialist",
      "Strategic Planning and Business Development Manager",
      "Artificial Intelligence (AI) Policy Advisor",
      "Ethics Consultancy and Compliance Specialist",
      "Crisis Management and Business Continuity Consultant",
      "Nonprofit Grant Writing and Fundraising Specialist",
      "Political Campaign Management and Strategy Specialist",
      "Diplomatic Liaison and International Relations Specialist",
      "University Admissions Counseling and Recruitment Specialist",
      "Academic Advising and Student Support Specialist",
      "Online Learning and Educational Technology Specialist",
      "Cyber Psychology and Digital Behavior Analyst",
      "Digital Ethics and Compliance Analyst",
      "Neuroscience Research and Development Specialist",
      "Psychometrics and Psychological Assessment Specialist",
      "Education Policy Analysis and Development Specialist",
      "Game Balance Design and Development Specialist",
      "Mobile Application Development and Design Specialist",
      "Technical Evangelism and Developer Advocacy Specialist",
      "Government Auditing and Compliance Specialist",
      "Economic Development and Policy Specialist",
      "Investment Banking Analysis and Strategy Specialist",
      "Risk Compliance and Regulatory Affairs Manager"
    ];

    // A pool of 200 industrial job titles
    $industrialTitles = [
      "Skilled Machinery Operations Specialist",
      "Expert Metal Welding Technician",
      "Production Workflow & Team Supervisor",
      "Quality Assurance & Compliance Inspector",
      "Certified Forklift Handling Specialist",
      "Warehouse Logistics & Stock Manager",
      "Industrial Electrical Systems Expert",
      "Mechanical Maintenance & Repair Specialist",
      "Facility Maintenance & Support Engineer",
      "Factory Floor Coordination Lead",
      "HVAC Installation & Maintenance Technician",
      "Workplace Safety & Health Officer",
      "Industrial Process & Efficiency Engineer",
      "Production Scheduling & Resource Planner",
      "On-Site Field Service Specialist",
      "Product Packaging & Assembly Technician",
      "CNC Machining & Precision Specialist",
      "Advanced Manufacturing Process Operator",
      "Heavy Machinery & Equipment Operator",
      "Automotive Maintenance & Repair Technician",
      "Assembly Line Productivity Worker",
      "Product Packaging & Sorting Associate",
      "Operations Coordination & Control Officer",
      "Strategic Logistics & Distribution Manager",
      "Facilities Administration & Operations Manager",
      "Construction Site Oversight Supervisor",
      "Building Code & Compliance Inspector",
      "Certified Crane Handling Operator",
      "Structural Steel Fabrication Worker",
      "Sheet Metal Forming & Fabrication Specialist",
      "Glass Cutting & Finishing Expert",
      "Professional Plumbing & Pipefitting Technician",
      "Concrete Mixing & Testing Technician",
      "Tunnel Boring & Excavation Machinery Operator",
      "Heavy-Duty Truck Mechanic & Technician",
      "Pipeline Maintenance & Monitoring Operator",
      "Offshore Oil Rig Operations Worker",
      "Wind Turbine Installation & Maintenance Technician",
      "Solar Panel Setup & Integration Specialist",
      "Renewable Energy Infrastructure Technician",
      "Mining Exploration & Extraction Engineer",
      "Drilling Tools & Operations Manager",
      "Sawmill Machinery & Lumber Processing Operator",
      "Timber Processing & Quality Technician",
      "Agricultural Field Operations Specialist",
      "Fishery Operations & Marine Technician",
      "Meat Processing & Packaging Specialist",
      "Dairy Farming & Production Worker",
      "Baking & Pastry Production Artisan",
      "Butchery & Meat Preparation Specialist",
      "Food Safety & Quality Inspector",
      "Brewing & Fermentation Process Specialist",
      "Distillery Operations & Quality Technician",
      "Chemical Plant Production Operator",
      "Pressurized Boiler Systems Operator",
      "Industrial Refrigeration & Cooling Technician",
      "Power Plant Generation Operator",
      "Textile Machinery & Fabric Operations Specialist",
      "Advanced Printing Press Operations Technician",
      "Woodworking & Carpentry Machine Operator",
      "Precision Metal Fabricator & Welder",
      "Shipyard Construction & Repair Worker",
      "Aircraft Maintenance & Repair Mechanic",
      "Avionics & Electronics Technician",
      "Railway Operations & Maintenance Technician",
      "Signal Equipment & Railway Systems Technician",
      "Elevator Installation & Safety Technician",
      "Water Treatment & Purification Specialist",
      "Waste Management & Environmental Technician",
      "Hazardous Material Handling Expert",
      "Nuclear Power Facility Operator",
      "Security Systems Installation & Maintenance Installer",
      "Cable Infrastructure & Wiring Technician",
      "Industrial Robotics & Automation Technician",
      "Advanced Industrial Automation Specialist",
      "Tool and Die Craftsmanship Maker",
      "Foundry Casting & Metallurgy Worker",
      "Industrial Paint & Coating Technician",
      "Skilled Leather Processing & Finishing Technician",
      "Paper Mill Processing & Operations Operator",
      "Plastic Molding & Shaping Technician",
      "Rubber Processing & Manufacturing Operator",
      "Creative Textile Pattern Designer",
      "Leather Tannery Handling Worker",
      "Sugar Mill Operations & Refining Technician",
      "Flour Milling & Grain Processing Worker",
      "Glass Blowing & Shaping Artisan",
      "Cement Mixing & Batch Plant Operator",
      "Roofing & Waterproofing Installation Specialist",
      "Ceramic Tile Setting & Flooring Technician",
      "Professional Pest Control & Infestation Technician"
    ];

    // Generate 600 job entries
    $jobs = [];
    for ($i = 0; $i < 600; $i++) {
      $type = (rand(0, 1) === 0) ? "functional" : "industrial";
      $jobTitle = $type === "functional" ? $functionalTitles[array_rand($functionalTitles)] : $industrialTitles[array_rand($industrialTitles)];
      $jobs[] = ["job_title" => $jobTitle, "type" => $type];
    }
    $top_uganda_districts = [
      "Kampala",
      "Wakiso",
      "Mukono",
      "Mbarara",
      "Gulu",
      "Lira",
      "Jinja",
      "Mbale",
      "Masaka",
      "Fort Portal",
      "Arua",
      "Entebbe",
      "Kasese",
    ];
    $distict_ids = District::where([])->pluck('id')->toArray();
    $tops_districts_ids = [];
    foreach ($top_uganda_districts as $key => $v) {
      $top_dis = District::where(['name' => $v])->first();
      if ($top_dis != null) {
        $tops_districts_ids[] = $top_dis->id;
      }
    }

    $general_district_ids = array_merge($distict_ids, array_fill(0, 50 * count($tops_districts_ids), $tops_districts_ids));

    $all_ser_ids = User::where([])->pluck('id')->toArray();
    $uganda_addresses = [
      "Kampala, Uganda",
      "Wakiso, Uganda",
      "Mukono, Uganda",
      "Mbarara, Uganda",
      "Gulu, Uganda",
      "Lira, Uganda",
      "Jinja, Uganda",
      "Mbale, Uganda",
      "Masaka, Uganda",
      "Fort Portal, Uganda",
      "Arua, Uganda",
      "Entebbe, Uganda",
      "Kasese, Uganda",
      'Kabale, Uganda',
      'Kisoro, Uganda',
    ];

    $jobDescriptions = [
      "Software Engineer" => "
          <h2>Software Engineer Responsibilities</h2>
          <p>A <strong>Software Engineer</strong> is responsible for designing, developing, and maintaining software systems. They work closely with cross-functional teams to deliver scalable and efficient solutions.</p>
          <h3>Key Responsibilities:</h3>
          <ul>
              <li>Write clean, scalable, and maintainable code.</li>
              <li>Design software architectures for new projects.</li>
              <li>Conduct software testing and debugging.</li>
              <li>Collaborate with UI/UX designers to enhance user experience.</li>
              <li>Review and optimize code for better performance.</li>
              <li>Stay updated with the latest programming languages and frameworks.</li>
          </ul>
          <h3>Technical Skills Required:</h3>
          <table border='1' cellpadding='5'>
              <tr>
                  <th>Skill</th>
                  <th>Proficiency Level</th>
              </tr>
              <tr>
                  <td>PHP, Python, Java, C++</td>
                  <td>Advanced</td>
              </tr>
              <tr>
                  <td>Frontend Technologies (HTML, CSS, JavaScript)</td>
                  <td>Intermediate</td>
              </tr>
              <tr>
                  <td>Database Management (MySQL, PostgreSQL)</td>
                  <td>Intermediate</td>
              </tr>
          </table>
          <blockquote>“Code is like humor. When you have to explain it, it’s bad.” – Cory House</blockquote>
      ",

      "Marketing Manager" => "
          <h2>Marketing Manager Responsibilities</h2>
          <p>A <em>Marketing Manager</em> is responsible for planning, implementing, and executing marketing campaigns to drive business growth and brand awareness.</p>
          <h3>Day-to-Day Duties:</h3>
          <ol>
              <li>Develop marketing strategies and brand positioning.</li>
              <li>Conduct market research to identify customer needs.</li>
              <li>Manage online and offline advertising campaigns.</li>
              <li>Oversee the content creation for websites and social media.</li>
              <li>Collaborate with sales teams to align marketing goals.</li>
              <li>Analyze marketing data to improve campaign performance.</li>
          </ol>
          <h3>Marketing Tools Used:</h3>
          <ul>
              <li><strong>Google Analytics</strong> - Website performance tracking</li>
              <li><strong>Facebook Ads Manager</strong> - Social media advertising</li>
              <li><strong>HubSpot</strong> - Customer relationship management</li>
          </ul>
          <p><strong>Did you know?</strong> Marketing managers who utilize data-driven strategies see a <span style='color: green; font-weight: bold;'>20% increase</span> in ROI!</p>
      ",

      "Project Manager" => "
          <h2>Project Manager Responsibilities</h2>
          <p>A <strong>Project Manager</strong> oversees and manages various business projects from conception to completion, ensuring they meet company goals and deadlines.</p>
          <h3>Primary Duties:</h3>
          <ul>
              <li>Define project scope, objectives, and deliverables.</li>
              <li>Create detailed project plans and schedules.</li>
              <li>Monitor progress and address challenges.</li>
              <li>Manage team members and assign tasks efficiently.</li>
              <li>Report project updates to stakeholders.</li>
              <li>Ensure projects stay within budget constraints.</li>
          </ul>
          <h3>Project Lifecycle Stages:</h3>
          <ol>
              <li><strong>Initiation:</strong> Identify project needs and objectives.</li>
              <li><strong>Planning:</strong> Develop a detailed roadmap.</li>
              <li><strong>Execution:</strong> Implement the project plan.</li>
              <li><strong>Monitoring:</strong> Track progress and mitigate risks.</li>
              <li><strong>Closure:</strong> Finalize and review the project.</li>
          </ol>
          <blockquote>“Failing to plan is planning to fail.” – Benjamin Franklin</blockquote>
      ",

      "Electrician" => "
          <h2>Electrician Responsibilities</h2>
          <p>An <strong>Electrician</strong> is a skilled tradesperson who installs, maintains, and repairs electrical systems in residential, commercial, and industrial settings.</p>
          <h3>Key Responsibilities:</h3>
          <ul>
              <li>Interpret electrical blueprints and technical diagrams.</li>
              <li>Install, maintain, and repair wiring, control systems, and lighting.</li>
              <li>Inspect electrical components for safety compliance.</li>
              <li>Diagnose and troubleshoot electrical malfunctions.</li>
              <li>Provide emergency electrical services when necessary.</li>
          </ul>
          <h3>Common Electrical Tools:</h3>
          <table border='1' cellpadding='5'>
              <tr>
                  <th>Tool</th>
                  <th>Function</th>
              </tr>
              <tr>
                  <td>Multimeter</td>
                  <td>Measures voltage, current, and resistance</td>
              </tr>
              <tr>
                  <td>Wire Stripper</td>
                  <td>Removes insulation from electrical wires</td>
              </tr>
              <tr>
                  <td>Voltage Tester</td>
                  <td>Detects electrical voltage in circuits</td>
              </tr>
          </table>
          <p>Did you know? Electricians must adhere to strict <strong>National Electrical Code (NEC)</strong> regulations for safety.</p>
      ",

      "HR Specialist" => "
          <h2>Human Resources Specialist Responsibilities</h2>
          <p>An <strong>HR Specialist</strong> is responsible for managing employee relations, recruiting, training, and ensuring compliance with labor laws.</p>
          <h3>Main Responsibilities:</h3>
          <ul>
              <li>Oversee the hiring process and recruitment.</li>
              <li>Implement and enforce company policies.</li>
              <li>Manage employee benefits and payroll.</li>
              <li>Resolve workplace conflicts and disputes.</li>
              <li>Facilitate training and professional development programs.</li>
          </ul>
          <h3>HR Best Practices:</h3>
          <ul>
              <li><strong>Employee Engagement:</strong> Boost motivation and productivity.</li>
              <li><strong>Diversity & Inclusion:</strong> Foster a welcoming workplace.</li>
              <li><strong>Legal Compliance:</strong> Stay updated with labor laws.</li>
          </ul>
          <p>HR specialists often use <strong>HR software</strong> like <em>Workday</em> or <em>BambooHR</em> to streamline administrative tasks.</p>
          <blockquote>“Train people well enough so they can leave, treat them well enough so they don’t want to.” – Richard Branson</blockquote>
      "
    ];

    $jobBenefits = [
      "Software Engineer" => "
          <h2>Benefits of Being a Software Engineer</h2>
          <p>Software engineering is one of the most <strong>in-demand</strong> and <em>well-paid</em> careers today. Professionals in this field enjoy a wide range of benefits.</p>
          
          <h3>Key Benefits:</h3>
          <ul>
              <li><strong>High Salary:</strong> Competitive earnings with room for growth.</li>
              <li><strong>Remote Work:</strong> Many software engineers can work from anywhere.</li>
              <li><strong>Job Security:</strong> Constant demand in various industries.</li>
              <li><strong>Innovative Work:</strong> Work on cutting-edge technology and solve real-world problems.</li>
              <li><strong>Freelance Opportunities:</strong> Flexibility to take on independent projects.</li>
              <li><strong>Continuous Learning:</strong> Opportunities to master new programming languages and frameworks.</li>
          </ul>
  
          <h3>Average Salary Based on Experience:</h3>
          <table border='1' cellpadding='5'>
              <tr>
                  <th>Experience Level</th>
                  <th>Average Annual Salary</th>
              </tr>
              <tr>
                  <td>Entry Level (0-2 years)</td>
                  <td>$60,000 - $80,000</td>
              </tr>
              <tr>
                  <td>Mid Level (3-5 years)</td>
                  <td>$85,000 - $110,000</td>
              </tr>
              <tr>
                  <td>Senior Level (6+ years)</td>
                  <td>$120,000+</td>
              </tr>
          </table>
  
          <blockquote>“The best thing about software engineering is that you are constantly learning.” – Linus Torvalds</blockquote>
      ",

      "Marketing Manager" => "
          <h2>Benefits of Being a Marketing Manager</h2>
          <p>Marketing managers play a crucial role in driving business growth and brand awareness. This career comes with numerous professional and financial rewards.</p>
          
          <h3>Top Benefits:</h3>
          <ul>
              <li><strong>Creative Freedom:</strong> Work on innovative campaigns and brand storytelling.</li>
              <li><strong>Career Growth:</strong> Opportunities to rise to executive-level positions.</li>
              <li><strong>Networking:</strong> Build relationships with top business leaders and influencers.</li>
              <li><strong>Performance-Based Bonuses:</strong> Many companies offer high bonuses based on campaign success.</li>
              <li><strong>Work-Life Balance:</strong> Many marketing roles offer hybrid or flexible schedules.</li>
          </ul>
  
          <h3>Marketing Trends Impacting Career Growth:</h3>
          <ol>
              <li>AI-driven digital marketing tools.</li>
              <li>Influencer marketing and social media dominance.</li>
              <li>Personalized and data-driven marketing strategies.</li>
          </ol>
  
          <blockquote>“Marketing is no longer about the stuff that you make, but about the stories you tell.” – Seth Godin</blockquote>
      ",

      "Project Manager" => "
          <h2>Benefits of Being a Project Manager</h2>
          <p>Project management is a highly respected career that allows professionals to lead teams, execute strategies, and drive success.</p>
  
          <h3>Major Benefits:</h3>
          <ul>
              <li><strong>Leadership Skills:</strong> Gain expertise in managing diverse teams.</li>
              <li><strong>Job Stability:</strong> Essential in every industry, from IT to construction.</li>
              <li><strong>High Earning Potential:</strong> Competitive salaries and bonuses.</li>
              <li><strong>Global Opportunities:</strong> PMP-certified managers are in demand worldwide.</li>
              <li><strong>Variety of Work:</strong> No two projects are the same, making the work engaging.</li>
          </ul>
  
          <h3>Certifications that Boost Salary:</h3>
          <table border='1' cellpadding='5'>
              <tr>
                  <th>Certification</th>
                  <th>Average Salary Increase</th>
              </tr>
              <tr>
                  <td>PMP (Project Management Professional)</td>
                  <td>+20%</td>
              </tr>
              <tr>
                  <td>Scrum Master Certification</td>
                  <td>+15%</td>
              </tr>
              <tr>
                  <td>PRINCE2 Certification</td>
                  <td>+18%</td>
              </tr>
          </table>
  
          <blockquote>“A goal without a plan is just a wish.” – Antoine de Saint-Exupéry</blockquote>
      ",

      "Electrician" => "
          <h2>Benefits of Being an Electrician</h2>
          <p>Electricians enjoy stable, hands-on careers with lucrative earnings and essential job roles.</p>
  
          <h3>Why Choose This Career?</h3>
          <ul>
              <li><strong>High Demand:</strong> Essential service required everywhere.</li>
              <li><strong>Job Security:</strong> Always needed in residential and commercial settings.</li>
              <li><strong>Good Salary:</strong> Experienced electricians earn excellent wages.</li>
              <li><strong>Low Student Debt:</strong> Many become electricians through apprenticeships.</li>
              <li><strong>Work Independently:</strong> Many electricians operate their own businesses.</li>
          </ul>
  
          <h3>Common Career Paths:</h3>
          <ol>
              <li>Residential Electrician</li>
              <li>Industrial Electrician</li>
              <li>Electrical Engineer</li>
              <li>Maintenance Technician</li>
          </ol>
  
          <blockquote>“Electricians keep the world running, one wire at a time.”</blockquote>
      ",

      "HR Specialist" => "
          <h2>Benefits of Being an HR Specialist</h2>
          <p>Human Resources (HR) is a fulfilling career that impacts company culture, employee well-being, and organizational success.</p>
  
          <h3>Key Benefits:</h3>
          <ul>
              <li><strong>People-Centric Work:</strong> Engage with employees to create a positive workplace.</li>
              <li><strong>Stable Career:</strong> Every organization needs HR professionals.</li>
              <li><strong>Career Progression:</strong> HR specialists can grow into managerial roles.</li>
              <li><strong>Competitive Salaries:</strong> Compensation grows with experience.</li>
              <li><strong>Opportunities in All Industries:</strong> Work in corporate, healthcare, education, etc.</li>
          </ul>
  
          <h3>HR Technologies You Might Use:</h3>
          <table border='1' cellpadding='5'>
              <tr>
                  <th>Software</th>
                  <th>Function</th>
              </tr>
              <tr>
                  <td>Workday</td>
                  <td>HR and Payroll Management</td>
              </tr>
              <tr>
                  <td>LinkedIn Recruiter</td>
                  <td>Talent Acquisition</td>
              </tr>
              <tr>
                  <td>BambooHR</td>
                  <td>Employee Management</td>
              </tr>
          </table>
  
          <blockquote>“Train people well enough so they can leave, treat them well enough so they don’t want to.” – Richard Branson</blockquote>
      "
    ];


    $xxx = 0;
    foreach ($jobs as $job) {
      $xxx++;
      $jobCategory = $job["type"] === "functional" ? $functional_cats_ids[array_rand($functional_cats_ids)] : $industry_cats_ids[array_rand($industry_cats_ids)];
      $jobTitle = $job["job_title"];
      $jobStatus = rand(0, 1) === 0 ? "Active" : "Inactive";
      $deadline = Carbon::now()->addDays(rand(1, 60))->format('Y-m-d');
      shuffle($general_district_ids);
      $districtId = $general_district_ids[array_rand($general_district_ids)];
      if (is_array($districtId)) {
        $districtId = $districtId[array_rand($districtId)];
      }
      $subCountyId = rand(1, 290);
      $address = "Kampala, Uganda";
      $vacanciesCount = rand(1, 15);
      $employmentStatus = ["Full-time", "Part-time", "Contract", "Internship"][array_rand(["Full-time", "Part-time", "Contract", "Internship"])];
      $workplace = ["Onsite", "Remote"][array_rand(["Onsite", "Remote"])];
      $responsibilities = "Key responsibilities for the job";
      $experienceField = "Relevant experience field";
      $experiencePeriod = "Experience duration";
      $showSalary = rand(0, 1) === 0 ? "Yes" : "No";
      $minimumSalary = rand(500, 1000);
      $maximumSalary = rand(1000, 2000);
      $benefits = "Job benefits";
      $jobIcon = "Icon or image path";
      $job = new Job();
      $job->title = $jobTitle;
      $job->posted_by_id = $all_ser_ids[array_rand($all_ser_ids)];
      $job->status = ['Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Inactive', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active'][rand(0, 20)];
      $job->deadline = $deadline;
      $job->category_id = $jobCategory;
      $job->district_id = $districtId;
      $job->sub_county_id = $subCountyId;
      $dis = District::find($districtId);
      $job->address = $dis->name . ", Uganda";
      $job->vacancies_count = $vacanciesCount;
      $job->employment_status = ['Full-time', 'Part-time', 'Contract', 'Internship'][rand(0, 3)];
      $job->workplace = ['Onsite', 'Remote', 'Hybrid'][rand(0, 2)];
      shuffle($jobDescriptions);
      $job->responsibilities = $jobDescriptions[array_rand($jobDescriptions)];
      $job->experience_field = [
        'Mobile Development',
        'Web Development',
        'Data Analysis',
        'Graphic Design',
        'Project Management',
        'Human Resources',
        'Sales',
        'Marketing',
        'Customer Support',
        'UI/UX Design',
        'Network Administration',
        'Quality Assurance',
        'Product Management',
        'Content Writing',
        'Business Analysis',
        'Cybersecurity',
        'Data Science',
        'Executive Assistance',
        'Mobile Development',
        'IT Support',
        'DevOps',
        'Database Administration',
        'Financial Analysis',
        'Technical Recruitment',
        'Digital Marketing',
        'Cloud Architecture',
        'Social Media Management',
        'Systems Engineering',
        'Operations Management',
        'Accounts Management',
        'SEO'
      ][rand(0, 30)];
      $job->experience_period = ['1-2 years', '2-3 years', '3-5 years', '5-7 years', '7-10 years', '10+ years'][rand(0, 5)];
      $job->show_salary = ['Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No'][rand(0, 1)];
      $job->minimum_salary = [
        100000,
        150000,
        200000,
        250000,
        300000,
        320000,
        350000,
        400000,
        450000,
        500000,
        550000,
        600000,
        650000,
        1000000,
        1200000,
        1500000,
        2000000,
        2500000,
        3000000,
        3500000,
        4000000,
        4500000,
        5000000,
      ][rand(0, 20)];

      $job->maximum_salary = $job->minimum_salary + [
        100000,
        150000,
        200000,
        250000,
        300000,
        320000,
        350000,
        400000,
        450000,
        450000,
        450000,
        500000,
        550000,
      ][rand(0, 12)];
      shuffle($jobBenefits);
      $job->benefits = $jobBenefits[array_rand($jobBenefits)];
      $company = User::find($job->posted_by_id);
      $job->job_icon = $company->company_logo;
      $job->gender = ['Any', 'Any', 'Any', 'Any', 'Any', 'Any', 'Any', 'Any', 'Male', 'Female'][rand(0, 9)];
      $job->min_age = rand(18, 30);
      $job->max_age  = $job->min_age + rand(5, 20);
      $job->required_video_cv = ['Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No'][rand(0, 1)];
      $job->minimum_academic_qualification = ['Certificate', 'Diploma', 'Degree', 'Masters', 'PhD'][rand(0, 4)];
      $job->application_method = ['Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Online', 'Email', 'In-person', 'Phone'][rand(0, 14)];
      $job->application_method_details = 'Application details';

      $job->save();
      echo "{$job->id}. Job created: " . $job->title . "<br>";
    }



    return;

    $jobs = [];
    $types = ["Full-time", "Part-time", "Contract", "Internship", "Freelance"];
    $titles = [
      "Software Engineer",
      "Web Developer",
      "Data Analyst",
      "Graphic Designer",
      "Project Manager",
      "HR Specialist",
      "Sales Executive",
      "Marketing Manager",
      "Customer Support Agent",
      "UI/UX Designer",
      "Network Administrator",
      "Quality Assurance Engineer",
      "Product Manager",
      "Content Writer",
      "Business Analyst",
      "Cybersecurity Specialist",
      "Data Scientist",
      "Executive Assistant",
      "Mobile App Developer",
      "IT Support Technician",
      "DevOps Engineer",
      "Database Administrator",
      "Financial Analyst",
      "Technical Recruiter",
      "Digital Marketing Specialist",
      "Cloud Architect",
      "Social Media Manager",
      "Systems Engineer",
      "Operations Manager",
      "Accounts Manager",
      "SEO Specialist"
    ];

    for ($i = 0; $i < 600; $i++) {
      $jobs[] = [
        'job_title' => $titles[array_rand($titles)],
        'type'      => $types[array_rand($types)]
      ];
    }
    $jobs = [];
    $types = ["Full-time", "Part-time", "Contract", "Internship", "Freelance"];
    $titles = [
      "Software Engineer",
      "Web Developer",
      "Data Analyst",
      "Graphic Designer",
      "Project Manager",
      "HR Specialist",
      "Sales Executive",
      "Marketing Manager",
      "Customer Support Agent",
      "UI/UX Designer",
      "Network Administrator",
      "Quality Assurance Engineer",
      "Product Manager",
      "Content Writer",
      "Business Analyst",
      "Cybersecurity Specialist",
      "Data Scientist",
      "Executive Assistant",
      "Mobile App Developer",
      "IT Support Technician",
      "DevOps Engineer",
      "Database Administrator",
      "Financial Analyst",
      "Technical Recruiter",
      "Digital Marketing Specialist",
      "Cloud Architect",
      "Social Media Manager",
      "Systems Engineer",
      "Operations Manager",
      "Accounts Manager",
      "SEO Specialist"
    ];

    for ($i = 0; $i < 600; $i++) {
      $jobs[] = [
        'job_title' => $titles[array_rand($titles)],
        'type'      => $types[array_rand($types)]
      ];
    }
    die("Functional: " . json_encode($functional_cats_ids) . "<br>Industry: " . json_encode($industry_cats_ids));
    //truncate jobs
    $functional_categories_names = [
      [
        'name' => 'Accounting/Finance',
        'description' => 'Jobs related to financial management, accounting, auditing, and financial analysis.',
        'seo_tags' => ['finance jobs', 'accounting careers', 'financial analyst positions']
      ],
      [
        'name' => 'Bank/Non-Bank Fin. Institution',
        'description' => 'Opportunities in banking, insurance, and other financial institutions.',
        'seo_tags' => ['banking jobs', 'insurance careers', 'financial institution positions']
      ],
      [
        'name' => 'Supply Chain/Procurement',
        'description' => 'Roles in logistics, supply chain management, and procurement.',
        'seo_tags' => ['supply chain jobs', 'procurement careers', 'logistics positions']
      ],
      [
        'name' => 'Education/Training',
        'description' => 'Positions in teaching, training, and educational administration.',
        'seo_tags' => ['teaching jobs', 'training careers', 'education positions']
      ],
      [
        'name' => 'Engineer/Architects',
        'description' => 'Jobs for engineers and architects in various fields.',
        'seo_tags' => ['engineering jobs', 'architect careers', 'technical positions']
      ],
      [
        'name' => 'Garments/Textile',
        'description' => 'Opportunities in the garment and textile industry.',
        'seo_tags' => ['textile jobs', 'garment industry careers', 'fashion positions']
      ],
      [
        'name' => 'HR/Org. Development',
        'description' => 'Roles in human resources and organizational development.',
        'seo_tags' => ['HR jobs', 'organizational development careers', 'human resources positions']
      ],
      [
        'name' => 'Gen Mgt/Admin',
        'description' => 'General management and administrative positions.',
        'seo_tags' => ['management jobs', 'administrative careers', 'executive positions']
      ],
      [
        'name' => 'Healthcare/Medical',
        'description' => 'Jobs in healthcare, medical services, and related fields.',
        'seo_tags' => ['healthcare jobs', 'medical careers', 'nursing positions']
      ],
      [
        'name' => 'Driving/Motor Technician',
        'description' => 'Opportunities for drivers and motor technicians.',
        'seo_tags' => ['driving jobs', 'motor technician careers', 'automotive positions']
      ],
      [
        'name' => 'Production/Operation',
        'description' => 'Roles in production, manufacturing, and operations management.',
        'seo_tags' => ['production jobs', 'manufacturing careers', 'operations positions']
      ],
      [
        'name' => 'Hospitality/Travel/Tourism',
        'description' => 'Jobs in the hospitality, travel, and tourism industry.',
        'seo_tags' => ['hospitality jobs', 'tourism careers', 'travel positions']
      ],
      [
        'name' => 'Commercial',
        'description' => 'Opportunities in commercial and business development.',
        'seo_tags' => ['commercial jobs', 'business development careers', 'sales positions']
      ],
      [
        'name' => 'Beauty Care/Health & Fitness',
        'description' => 'Roles in beauty care, health, and fitness services.',
        'seo_tags' => ['beauty care jobs', 'health and fitness careers', 'wellness positions']
      ],
      [
        'name' => 'IT & Telecommunication',
        'description' => 'Jobs in information technology and telecommunications.',
        'seo_tags' => ['IT jobs', 'telecommunication careers', 'tech positions']
      ],
      [
        'name' => 'Marketing/Sales',
        'description' => 'Opportunities in marketing, sales, and business promotion.',
        'seo_tags' => ['marketing jobs', 'sales careers', 'business promotion positions']
      ],
      [
        'name' => 'Customer Service/Call Centre',
        'description' => 'Roles in customer service and call center operations.',
        'seo_tags' => ['customer service jobs', 'call center careers', 'support positions']
      ],
      [
        'name' => 'Media/Ad./Event Mgt.',
        'description' => 'Jobs in media, advertising, and event management.',
        'seo_tags' => ['media jobs', 'advertising careers', 'event management positions']
      ],
      [
        'name' => 'Pharmaceutical',
        'description' => 'Opportunities in the pharmaceutical industry.',
        'seo_tags' => ['pharmaceutical jobs', 'pharmacy careers', 'biotech positions']
      ],
      [
        'name' => 'Electrician/Construction/Repair',
        'description' => 'Roles for electricians, construction workers, and repair technicians.',
        'seo_tags' => ['electrician jobs', 'construction careers', 'repair positions']
      ],
      [
        'name' => 'Agro (Plant/Animal/Fisheries)',
        'description' => 'Jobs in agriculture, including plant, animal, and fisheries sectors.',
        'seo_tags' => ['agriculture jobs', 'farming careers', 'fisheries positions']
      ],
      [
        'name' => 'NGO/Development',
        'description' => 'Opportunities in non-governmental organizations and development projects.',
        'seo_tags' => ['NGO jobs', 'development careers', 'non-profit positions']
      ],
      [
        'name' => 'Research/Consultancy',
        'description' => 'Roles in research and consultancy services.',
        'seo_tags' => ['research jobs', 'consultancy careers', 'analyst positions']
      ],
      [
        'name' => 'Receptionist/PS',
        'description' => 'Jobs for receptionists and personal assistants.',
        'seo_tags' => ['receptionist jobs', 'personal assistant careers', 'front desk positions']
      ],
      [
        'name' => 'Data Entry/Operator/BPO',
        'description' => 'Opportunities in data entry, operations, and business process outsourcing.',
        'seo_tags' => ['data entry jobs', 'BPO careers', 'operations positions']
      ],
      [
        'name' => 'Design/Creative',
        'description' => 'Roles in design and creative industries.',
        'seo_tags' => ['design jobs', 'creative careers', 'graphic design positions']
      ],
      [
        'name' => 'Security/Support Service',
        'description' => 'Jobs in security and support services.',
        'seo_tags' => ['security jobs', 'support service careers', 'guard positions']
      ],
      [
        'name' => 'Law/Legal',
        'description' => 'Opportunities in the legal field.',
        'seo_tags' => ['law jobs', 'legal careers', 'attorney positions']
      ],
      [
        'name' => 'Company Secretary/Regulatory',
        'description' => 'Roles for company secretaries and regulatory compliance officers.',
        'seo_tags' => ['company secretary jobs', 'regulatory careers', 'compliance positions']
      ],
      [
        'name' => 'Others',
        'description' => 'Miscellaneous job categories not covered above.',
        'seo_tags' => ['miscellaneous jobs', 'other careers', 'various positions']
      ]
    ];
    $industry_categories_names = [
      [
        'name' => 'Agro-based Industry',
        'description' => 'Industries related to agriculture, including farming, livestock, and fisheries.',
        'seo_tags' => ['agriculture jobs', 'farming careers', 'livestock positions']
      ],
      [
        'name' => 'Architecture/Engineering/Construction',
        'description' => 'Jobs in architecture, engineering, and construction sectors.',
        'seo_tags' => ['architecture jobs', 'engineering careers', 'construction positions']
      ],
      [
        'name' => 'Automobile/Industrial Machinery',
        'description' => 'Opportunities in the automobile and industrial machinery industries.',
        'seo_tags' => ['automobile jobs', 'industrial machinery careers', 'mechanical positions']
      ],
      [
        'name' => 'Banking/Financial Institutions',
        'description' => 'Roles in banking, financial services, and related institutions.',
        'seo_tags' => ['banking jobs', 'financial services careers', 'finance positions']
      ],
      [
        'name' => 'Education',
        'description' => 'Positions in educational institutions, including teaching and administration.',
        'seo_tags' => ['education jobs', 'teaching careers', 'academic positions']
      ],
      [
        'name' => 'Electronics/Consumer Durables',
        'description' => 'Jobs in the electronics and consumer durables sectors.',
        'seo_tags' => ['electronics jobs', 'consumer durables careers', 'tech positions']
      ],
      [
        'name' => 'Energy/Power/Fuel',
        'description' => 'Opportunities in the energy, power, and fuel industries.',
        'seo_tags' => ['energy jobs', 'power sector careers', 'fuel industry positions']
      ],
      [
        'name' => 'Garments/Textile',
        'description' => 'Roles in the garments and textile industry.',
        'seo_tags' => ['textile jobs', 'garment industry careers', 'fashion positions']
      ],
      [
        'name' => 'Government/Semi-Government/Autonomous',
        'description' => 'Jobs in government, semi-government, and autonomous organizations.',
        'seo_tags' => ['government jobs', 'public sector careers', 'autonomous positions']
      ],
      [
        'name' => 'Pharmaceuticals',
        'description' => 'Opportunities in the pharmaceutical industry.',
        'seo_tags' => ['pharmaceutical jobs', 'pharmacy careers', 'biotech positions']
      ],
      [
        'name' => 'Hospital/Diagnostic Centers',
        'description' => 'Roles in hospitals and diagnostic centers.',
        'seo_tags' => ['hospital jobs', 'healthcare careers', 'medical positions']
      ],
      [
        'name' => 'Airline/Travel/Tourism',
        'description' => 'Jobs in the airline, travel, and tourism industry.',
        'seo_tags' => ['airline jobs', 'travel careers', 'tourism positions']
      ],
      [
        'name' => 'Manufacturing (Light Industry)',
        'description' => 'Opportunities in light manufacturing industries.',
        'seo_tags' => ['light manufacturing jobs', 'production careers', 'factory positions']
      ],
      [
        'name' => 'Manufacturing (Heavy Industry)',
        'description' => 'Roles in heavy manufacturing industries.',
        'seo_tags' => ['heavy manufacturing jobs', 'industrial careers', 'engineering positions']
      ],
      [
        'name' => 'Hotel/Restaurant',
        'description' => 'Jobs in the hotel and restaurant industry.',
        'seo_tags' => ['hotel jobs', 'restaurant careers', 'hospitality positions']
      ],
      [
        'name' => 'Information Technology (IT)',
        'description' => 'Opportunities in the information technology sector.',
        'seo_tags' => ['IT jobs', 'tech careers', 'software positions']
      ],
      [
        'name' => 'Logistics/Transportation',
        'description' => 'Roles in logistics and transportation industries.',
        'seo_tags' => ['logistics jobs', 'transportation careers', 'supply chain positions']
      ],
      [
        'name' => 'Entertainment/Recreation',
        'description' => 'Jobs in the entertainment and recreation industry.',
        'seo_tags' => ['entertainment jobs', 'recreation careers', 'media positions']
      ],
      [
        'name' => 'Media/Advertising/Event Management',
        'description' => 'Opportunities in media, advertising, and event management.',
        'seo_tags' => ['media jobs', 'advertising careers', 'event management positions']
      ],
      [
        'name' => 'NGO/Development',
        'description' => 'Roles in non-governmental organizations and development projects.',
        'seo_tags' => ['NGO jobs', 'development careers', 'non-profit positions']
      ],
      [
        'name' => 'Real Estate/Development',
        'description' => 'Jobs in real estate and property development.',
        'seo_tags' => ['real estate jobs', 'property development careers', 'construction positions']
      ],
      [
        'name' => 'Wholesale/Retail/Export-Import',
        'description' => 'Opportunities in wholesale, retail, and export-import sectors.',
        'seo_tags' => ['wholesale jobs', 'retail careers', 'export-import positions']
      ],
      [
        'name' => 'Telecommunication',
        'description' => 'Roles in the telecommunication industry.',
        'seo_tags' => ['telecommunication jobs', 'networking careers', 'IT positions']
      ],
      [
        'name' => 'Food & Beverage Industry',
        'description' => 'Jobs in the food and beverage industry.',
        'seo_tags' => ['food industry jobs', 'beverage careers', 'hospitality positions']
      ],
      [
        'name' => 'Security Services',
        'description' => 'Opportunities in security services.',
        'seo_tags' => ['security jobs', 'guard careers', 'protection positions']
      ],
      [
        'name' => 'Fire, Safety & Protection',
        'description' => 'Roles in fire safety and protection services.',
        'seo_tags' => ['fire safety jobs', 'protection careers', 'emergency services positions']
      ],
      [
        'name' => 'E-Commerce/F-Commerce',
        'description' => 'Jobs in e-commerce and f-commerce sectors.',
        'seo_tags' => ['e-commerce jobs', 'online retail careers', 'digital marketing positions']
      ]
    ];

    $i = 0;
    foreach ($functional_categories_names as $key => $v) {
      $i++;
      $exists = JobCategory::where([
        'name' => $v['name']
      ])->first();
      if ($exists != null) {
        echo "$i. Category already exists: " . $v['name'] . " <br>";
        continue;
      }
      $category = new JobCategory();
      $category->name = $v['name'];
      $category->description = $v['description'];
      $category->type = 'Functional';
      $category->icon = null;
      $category->slug = Str::slug($v['name']);
      $category->status = 'Active';
      $category->jobs_count = 0;
      $category->tags = json_encode($v['seo_tags']);
      $category->save();
      echo "$i. Category created: $category->name <br>";
    }

    $i = 0;
    foreach ($industry_categories_names as $key => $v) {
      $i++;
      $exists = JobCategory::where([
        'name' => $v['name']
      ])->first();
      if ($exists != null) {
        echo "$i. Category already exists: " . $v['name'] . "<br>";
        continue;
      }
      $category = new JobCategory();
      $category->name = $v['name'];
      $category->description = $v['description'];
      $category->type = 'Industry';
      $category->icon = null;
      $category->slug = Str::slug($v['name']);
      $category->status = 'Active';
      $category->jobs_count = 0;
      $category->tags = json_encode($v['seo_tags']);
      $category->save();
      echo "$i. Category created: $category->name <br>";
    }
  }
  function gen_companies()
  {

    return;
    foreach (User::where([])->get() as $key => $v) {
      $url = url('storage/' . $v->avatar);
      echo '<img src="' . $url . '" alt="' . $v->name . '" style="width: 100px; height: 100px; border-radius: 50%;">';
      $v->avatar = $url;
      continue;

      if ($v->sex == 'Male') {
        $v->avatar = 'images/' . rand(26, 50) . '.jpg';
      } else {
        $v->sex = 'Female';
        $v->avatar = 'images/' . rand(1, 25) . '.jpg';
      }
      echo $v->avatar . " - " . $v->sex . "<br>";
      $v->save();
    }
    die();
    //$company
    $firstCompany = User::where([])->first();
    $max_count = 250;
    $ugandaCompanies = array(
      "MTN Uganda",                            // 1
      "Airtel Uganda",                         // 2
      "Uganda Telecom",                        // 3
      "Stanbic Bank Uganda",                   // 4
      "dfcu Bank",                             // 5
      "Centenary Bank",                        // 6
      "Equity Bank Uganda",                    // 7
      "Absa Bank Uganda",                      // 8 (formerly Barclays Bank Uganda)
      "Housing Finance Bank",                  // 9
      "Coca-Cola Beverages Africa (Uganda)",   // 10
      "Nile Breweries Limited",                // 11
      "Uganda Breweries Limited",              // 12
      "Roke Telkom",                           // 13
      "Smile Communications Uganda",           // 14
      "Uganda Clays Limited",                  // 15
      "Roofings Limited",                      // 16
      "Mukwano Industries Uganda Limited",     // 17
      "Movit Products Limited",                // 18
      "Bidco Uganda Limited",                  // 19
      "Kakira Sugar Works",                    // 20
      "Mehta Group (Sugar Corporation of Uganda)", // 21
      "Ugachick Poultry Breeders",             // 22
      "Pearl Dairy Farms Limited",             // 23
      "Fresh Dairy Uganda",                    // 24
      "Uganda Coffee Development Authority (UCDA)", // 25 (Govt. authority, listed for completeness)
      "Uganda Tea Corporation",                // 26
      "Uganda Airlines",                       // 27
      "National Water and Sewerage Corporation (NWSC)", // 28
      "National Social Security Fund (NSSF) Uganda",    // 29
      "Uganda Electricity Generation Company Ltd (UEGCL)", // 30
      "Uganda Electricity Transmission Company Ltd (UETCL)", // 31
      "Umeme Limited",                         // 32
      "Uganda Development Bank",               // 33
      "Standard Chartered Bank Uganda",        // 34
      "Tropical Bank Uganda",                  // 35
      "PostBank Uganda",                       // 36
      "Finance Trust Bank",                    // 37
      "Bank of India Uganda",                  // 38
      "Bank of Baroda Uganda",                 // 39
      "Guaranty Trust Bank (Uganda)",          // 40
      "ICEA Lion Uganda",                      // 41
      "UAP Old Mutual Uganda",                 // 42
      "Britam Insurance Uganda",               // 43
      "Goldstar Insurance Company",            // 44
      "Liberty Life Assurance Uganda",         // 45
      "Jubilee Insurance Uganda",              // 46
      "Phoenix Assurance Uganda",              // 47
      "NIKO Insurance (Sanlam Uganda)",        // 48
      "Sanlam Uganda",                         // 49
      "APA Insurance Uganda",                  // 50
      "Agricultural Business Initiative (aBi)", // 51
      "Pearl Microfinance",                    // 52
      "Pride Microfinance Ltd",                // 53
      "EFC Uganda Limited (MFI)",              // 54
      "Opportunity Bank Uganda",               // 55
      "Bayport Financial Services Uganda",     // 56
      "ZenoTel",                               // 57
      "Simba Telecom",                         // 58
      "Bee Natural Uganda",                    // 59
      "Great Lakes Coffee Company Uganda",     // 60
      "Cipla Quality Chemical Industries",     // 61
      "Kampala Pharmaceutical Industries",     // 62
      "Rene Industries",                       // 63
      "Quality Chemicals Limited",             // 64
      "Eram (U) Limited",                     // 65
      "Steel and Tube Industries",             // 66
      "National Cement Company Uganda (Simba Cement)", // 67
      "Tororo Cement Limited",                 // 68
      "Hima Cement Limited",                   // 69
      "Athan Corporation",                     // 70 (fictitious placeholder)
      "Biyinzika Poultry International",       // 71
      "AMPROC Sugar Uganda",                   // 72 (fictitious placeholder)
      "Victoria Seeds Ltd",                    // 73
      "Farm Engineering Industries Uganda",    // 74
      "East African Packaging Solutions",      // 75
      "BM Technical Services",                 // 76
      "Aponye Uganda Limited",                 // 77
      "BMK Group",                             // 78
      "Africell Uganda",                       // 79 (formerly an operator, exited in 2021)
      "Warid Telecom Uganda",                  // 80 (acquired by Airtel)
      "Uganda Printing and Publishing Corporation", // 81
      "Picfare Industries Limited",            // 82
      "Graphic Systems Uganda",                // 83
      "Millennium Infocom",                    // 84 (fictitious placeholder)
      "Phoenix Logistics",                     // 85 (fictitious placeholder)
      "Freba Investments",                     // 86 (fictitious placeholder)
      "Pramukh Steel Limited",                 // 87
      "Kasese Cobalt Company",                 // 88
      "Kilembe Mines",                         // 89
      "Hoima Sugar Limited",                   // 90
      "Zoe Foods (U) Ltd",                     // 91 (fictitious placeholder)
      "Blue Wave Beverages",                   // 92 (fictitious placeholder)
      "Prime Agro Limited",                    // 93 (fictitious placeholder)
      "Nature's Best Uganda",                  // 94 (fictitious placeholder)
      "Spear Motors Limited",                  // 95
      "Toyota Uganda",                         // 96
      "Cooper Motors Corporation (CMC)",       // 97
      "Motorcare Uganda",                      // 98
      "Uganda Fish Packers",                   // 99 (fictitious placeholder)
      "Aquafresh Uganda",                      // 100 (fictitious placeholder)
      "Kampala Cement Company",                // 101
      "Kent Impex Uganda",                     // 102 (fictitious placeholder)
      "Padma Limited",                         // 103 (fictitious placeholder)
      "Afrisol Energy Uganda",                 // 104 (fictitious placeholder)
      "AgroWays Uganda",                       // 105 (fictitious placeholder)
      "Alpha Dairy Products",                  // 106 (fictitious placeholder)
      "ALTX East Africa",                      // 107
      "Anchor Beverages",                      // 108 (fictitious placeholder)
      "Ankole Coffee Producers",               // 109 (fictitious placeholder)
      "Aqua Harvest Uganda",                   // 110 (fictitious placeholder)
      "Aurum Roses",                           // 111 (fictitious placeholder)
      "Bakyawa Construction",                  // 112 (fictitious placeholder)
      "Bakus Food Industries",                 // 113 (fictitious placeholder)
      "Bantu Milk Uganda",                     // 114 (fictitious placeholder)
      "BAP Properties",                        // 115 (fictitious placeholder)
      "BBI Uganda",                            // 116 (fictitious placeholder)
      "Bee House Africa",                      // 117 (fictitious placeholder)
      "Benchmark Contractors Uganda",          // 118 (fictitious placeholder)
      "Bendex Uganda",                         // 119 (fictitious placeholder)
      "Beta Healthcare Uganda",                // 120 (fictitious placeholder)
      "Bic Tours Uganda",                      // 121
      "Biva Foods",                            // 122 (fictitious placeholder)
      "Biyinzika Investments",                 // 123 (fictitious placeholder)
      "Blue Crane Communications",             // 124 (fictitious placeholder)
      "Bond Pharmaceuticals",                  // 125 (fictitious placeholder)
      "Bravan Constructions",                  // 126 (fictitious placeholder)
      "Breeze Agro",                           // 127 (fictitious placeholder)
      "Brenda Farm Products",                  // 128 (fictitious placeholder)
      "Bulk Traders Uganda",                   // 129 (fictitious placeholder)
      "Bulwark Security Services",             // 130 (fictitious placeholder)
      "Bushenyi Dairy Industries",             // 131 (fictitious placeholder)
      "Bwindi Coffee Growers",                 // 132 (fictitious placeholder)
      "Cabana Logistics",                      // 133 (fictitious placeholder)
      "CafePap Uganda",                        // 134
      "Camal Investments",                     // 135 (fictitious placeholder)
      "CamTech Diagnostics Uganda",            // 136
      "Canary Foods",                          // 137 (fictitious placeholder)
      "Canaan Valley Farms",                   // 138 (fictitious placeholder)
      "Capital Ventures Uganda",               // 139 (fictitious placeholder)
      "Capitol Caterers",                      // 140 (fictitious placeholder)
      "Cargo Masters Uganda",                  // 141 (fictitious placeholder)
      "Cassava Millers Uganda",                // 142 (fictitious placeholder)
      "Ceresco Uganda",                        // 143 (fictitious placeholder)
      "Checkers Foods",                        // 144 (fictitious placeholder)
      "Cherish Uganda",                       // 145 (fictitious placeholder)
      "Cocoa Growers Cooperative",             // 146 (fictitious placeholder)
      "Cold Chain Solutions Uganda",           // 147 (fictitious placeholder)
      "Commodity Solutions Ltd",               // 148 (fictitious placeholder)
      "Creative Edge Printers",                // 149 (fictitious placeholder)
      "Crested Crane Holdings",                // 150 (fictitious placeholder)
      "Crested Stocks & Securities",           // 151
      "Crimson Agro",                          // 152 (fictitious placeholder)
      "Crown Bottlers Uganda",                 // 153 (fictitious placeholder)
      "Daks Couriers",                         // 154
      "Data Care Uganda",                      // 155 (fictitious placeholder)
      "Delight Uganda",                        // 156 (fictitious placeholder)
      "Dembe Distributors",                    // 157
      "Design Hub Kampala",                    // 158
      "Dero Exports",                          // 159 (fictitious placeholder)
      "Desbro Uganda",                         // 160
      "Diamond Trust Bank Uganda",             // 161
      "Dolphin Suites",                        // 162
      "E-Collect Uganda",                      // 163 (fictitious placeholder)
      "Eagle Air (Uganda)",                    // 164
      "East African Basic Foods",              // 165 (fictitious placeholder)
      "Eastern Plastics Uganda",               // 166 (fictitious placeholder)
      "Eco Bank Uganda",                       // 167 (Ecobank Uganda)
      "Eco-Fuel Africa",                       // 168
      "Eco-Pad Solutions",                     // 169 (fictitious placeholder)
      "Eco Shoes Uganda",                      // 170 (fictitious placeholder)
      "Elegance Finance",                      // 171
      "Elephant Farms",                        // 172 (fictitious placeholder)
      "Elm U Ltd",                             // 173 (fictitious placeholder)
      "Equator Bottlers",                      // 174 (fictitious placeholder)
      "Equator Seeds Ltd",                     // 175
      "Equinox Coffee Exporters",              // 176 (fictitious placeholder)
      "Erima Pharmaceuticals",                 // 177 (fictitious placeholder)
      "Esco Uganda",                           // 178
      "Euro Packaging Uganda",                 // 179 (fictitious placeholder)
      "Everest International Group",           // 180 (fictitious placeholder)
      "Fang Fang Group",                       // 181 (restaurants/hotels)
      "Fast Agro Logistics",                   // 182 (fictitious placeholder)
      "Filmax Uganda",                         // 183 (fictitious placeholder)
      "Fine Spinners Uganda",                  // 184
      "Finca Uganda",                          // 185
      "Flitlinks International",               // 186 (fictitious placeholder)
      "Fly Uganda",                            // 187
      "Fort Portal Tea Company",               // 188
      "Frotex Uganda",                         // 189 (fictitious placeholder)
      "GA Insurance Uganda",                   // 190
      "Gagawala Trading",                      // 191 (fictitious placeholder)
      "Gaso Transport Services",               // 192 (fictitious placeholder)
      "Geo Lodges Uganda",                     // 193
      "Global Paints Uganda",                  // 194 (fictitious placeholder)
      "Goldstar Confectioneries",              // 195 (fictitious placeholder)
      "Green Bio Energy Uganda",               // 196
      "Green Impact Uganda",                   // 197 (fictitious placeholder)
      "Green Label Foods",                     // 198 (fictitious placeholder)
      "Greenfields Foods",                     // 199 (fictitious placeholder)
      "Gulu Agricultural Development Company", // 200
      "Hakuna Matata Safaris",                 // 201 (fictitious placeholder)
      "Hard Rock Aggregates",                  // 202 (fictitious placeholder)
      "Hilltop Hotel & Resorts",               // 203 (fictitious placeholder)
      "Hwan Sung Industries",                  // 204
      "iDROID Africa (Uganda)",                // 205
      "Interswitch East Africa (Uganda)",      // 206
      "JESA Farm Dairy",                       // 207
      "Jinja Bakers",                          // 208 (fictitious placeholder)
      "Jonah Group Uganda",                    // 209 (fictitious placeholder)
      "Jordan Pharmaceuticals",                // 210 (fictitious placeholder)
      "Jowal Foods",                           // 211 (fictitious placeholder)
      "Jumia Uganda",                          // 212
      "Kachain Logistics",                     // 213 (fictitious placeholder)
      "Kalita Transporters",                   // 214 (fictitious placeholder)
      "Kampala Executive Aviation",            // 215
      "Kampala Serena Hotel",                  // 216
      "Karamoja Minerals",                     // 217 (fictitious placeholder)
      "Kasangati Millers",                     // 218 (fictitious placeholder)
      "Kayonza Tea Factory",                   // 219
      "Keba Investments",                      // 220 (fictitious placeholder)
      "Kenlon Industries",                     // 221 (fictitious placeholder)
      "Kisindi Coffee Estate",                 // 222 (fictitious placeholder)
      "Kisubi Hospital",                       // 223 (Private hospital, listed here)
      "Kiwanga Leather Tannery",              // 224 (fictitious placeholder)
      "Kodo Rice Growers",                     // 225 (fictitious placeholder)
      "Komo Dairy",                            // 226 (fictitious placeholder)
      "Kyosiga Agro Industries",               // 227 (fictitious placeholder)
      "Lake Bounty Fishing Company",           // 228 (fictitious placeholder)
      "Lakeside Dairy",                        // 229 (fictitious placeholder)
      "Lake Victoria Marine Services",         // 230 (fictitious placeholder)
      "Lakeland Holdings",                     // 231 (fictitious placeholder)
      "Lira Resort & Hotel",                   // 232 (fictitious placeholder)
      "Little Eye Bee Farm",                   // 233 (fictitious placeholder)
      "Lugazi Industries",                     // 234 (fictitious placeholder)
      "Lugazi Interiors",                      // 235 (fictitious placeholder)
      "Lumious Tech",                          // 236 (fictitious placeholder)
      "Lunch Box Uganda",                      // 237 (fictitious placeholder)
      "Luo Agro Tech",                         // 238 (fictitious placeholder)
      "Madhvani Group",                        // 239
      "Makerere University Holdings",          // 240 (MU does have certain business arms)
      "Malcom Construction",                   // 241 (fictitious placeholder)
      "Mango Tree Educational Enterprises",    // 242
      "Mantrac Uganda",                        // 243
      "MaryLou Agro Seeds",                    // 244 (fictitious placeholder)
      "Masaka Creamery",                       // 245 (fictitious placeholder)
      "Masindi Cotton Company",                // 246 (fictitious placeholder)
      "Mechtools Uganda",                      // 247
      "Metro Cement Limited",                  // 248
      "Mityana Agro Machinery",                // 249 (fictitious placeholder)
      "Mubende Pineapple Factory"              // 250 (fictitious placeholder)
    );
    $sex = [
      'Male',
      'Female'
    ];
    $religion = [
      'Islam',
      'Christianity',
      'Hinduism',
      'Buddhism',
      'Judaism',
      'Atheism',
      'Agnosticism',
      'Other'
    ];
    $objectives = [
      "To leverage my skills and experience in a dynamic organization to achieve its goals and objectives.",
      "Seeking a challenging position in a reputable organization to expand my learnings, knowledge, and skills.",
      "To secure a responsible career opportunity to fully utilize my training and skills, while making a significant contribution to the success of the company.",
      "To obtain a position that will enable me to use my strong organizational skills, educational background, and ability to work well with people.",
      "To work in an environment which encourages me to succeed and grow professionally where I can utilize my skills and knowledge appropriately.",
      "To enhance my professional skills in a dynamic and stable workplace.",
      "To solve problems in a creative and effective manner in a challenging position.",
      "To build a long-term career in a progressive organization that offers opportunities for career growth.",
      "To work in a company with a positive atmosphere that provides continuous learning and development opportunities.",
      "To obtain a position that challenges me and provides me the opportunity to reach my full potential professionally and personally.",
      "To bring my strong sense of dedication, motivation, and responsibility to a company that values hard work and commitment.",
      "To contribute to the success of the company through my skills and experience in a meaningful way.",
      "To secure a position where I can effectively contribute my skills and ensure my professional growth.",
      "To work in a challenging environment that provides generous opportunities for learning and advancement in my career.",
      "To be part of a reputed organization which provides a steady career growth along with job satisfaction and challenges.",
      "To utilize my skills and abilities in an organization that offers professional growth while being resourceful, innovative, and flexible.",
      "To obtain a position that will allow me to use my strong organizational skills, educational background, and ability to work well with people.",
      "To work in a stimulating environment where I can apply my skills and knowledge to achieve the organization's goals.",
      "To secure a challenging position in a reputable organization to expand my learnings, knowledge, and skills.",
      "To be associated with a progressive organization that gives me scope to apply my knowledge and skills, and to be a part of a team that dynamically works towards the growth of the organization."
    ];
    $special_qualifications = [
      "Certified Public Accountant (CPA)",
      "Project Management Professional (PMP)",
      "Certified Information Systems Security Professional (CISSP)",
      "Six Sigma Black Belt",
      "Certified ScrumMaster (CSM)",
      "Microsoft Certified Solutions Expert (MCSE)",
      "Cisco Certified Network Associate (CCNA)",
      "Certified Ethical Hacker (CEH)",
      "AWS Certified Solutions Architect",
      "Google Analytics Certified",
      "Certified Financial Planner (CFP)",
      "Certified Human Resources Professional (CHRP)",
      "Lean Six Sigma Green Belt",
      "Certified Data Professional (CDP)",
      "Certified Supply Chain Professional (CSCP)",
      "Certified Marketing Professional (CMP)",
      "Certified Information Systems Auditor (CISA)",
      "Certified Business Analysis Professional (CBAP)",
      "Certified Fraud Examiner (CFE)",
      "Certified Internal Auditor (CIA)",
      "Certified Information Security Manager (CISM)",
      "Certified in Risk and Information Systems Control (CRISC)",
      "Certified Cloud Security Professional (CCSP)",
      "Certified Data Privacy Solutions Engineer (CDPSE)",
      "Certified in the Governance of Enterprise IT (CGEIT)",
      "Certified Information Privacy Professional (CIPP)",
      "Certified Information Privacy Manager (CIPM)",
      "Certified Information Privacy Technologist (CIPT)",
      "Certified Blockchain Professional (CBP)",
      "Certified Artificial Intelligence Practitioner (CAIP)",
      "Certified Internet of Things (IoT) Professional",
      "Certified DevOps Engineer",
      "Certified Kubernetes Administrator (CKA)",
      "Certified Kubernetes Application Developer (CKAD)",
      "Certified Machine Learning Specialist",
      "Certified Data Scientist",
      "Certified Big Data Professional",
      "Certified Agile Project Manager",
      "Certified Digital Marketing Professional",
      "Certified Social Media Strategist"
    ];
    $career_summaries =
      [
        "Experienced marketing professional with a proven track record in digital marketing, social media strategy, and brand management.",
        "Seasoned project manager with over 10 years of experience in leading cross-functional teams and delivering projects on time and within budget.",
        "Highly skilled software developer with expertise in full-stack development, cloud computing, and DevOps practices.",
        "Accomplished financial analyst with a strong background in financial modeling, budgeting, and forecasting.",
        "Results-driven sales executive with a history of exceeding sales targets and driving revenue growth.",
        "Creative graphic designer with a passion for visual storytelling and a portfolio of successful branding projects.",
        "Dedicated human resources professional with experience in talent acquisition, employee relations, and performance management.",
        "Strategic business consultant with a focus on process improvement, change management, and organizational development.",
        "Innovative product manager with a knack for identifying market opportunities and launching successful products.",
        "Experienced data scientist with a deep understanding of machine learning, data mining, and statistical analysis.",
        "Skilled network engineer with expertise in network design, implementation, and troubleshooting.",
        "Proficient content writer with a talent for creating engaging and SEO-friendly content for various platforms.",
        "Certified public accountant with extensive experience in auditing, tax preparation, and financial reporting.",
        "Talented UX/UI designer with a user-centered approach to designing intuitive and aesthetically pleasing interfaces.",
        "Knowledgeable supply chain manager with a strong background in logistics, procurement, and inventory management.",
        "Experienced legal professional with expertise in corporate law, contract negotiation, and compliance.",
        "Dynamic marketing strategist with a focus on market research, campaign planning, and brand positioning.",
        "Versatile administrative assistant with excellent organizational skills and a track record of supporting executive teams.",
        "Proficient IT support specialist with experience in troubleshooting hardware and software issues and providing technical assistance.",
        "Dedicated customer service representative with a commitment to providing exceptional service and resolving customer issues."
      ];
    $faker = \Faker\Factory::create();

    $i = 0;
    foreach ($ugandaCompanies  as $key => $ugandaCompany) {
      $i++;
      $company = User::where('company_name', $ugandaCompany)->first();
      $public_path = public_path('storage/images/');
      $list_of_files = scandir($public_path);
      //remove the first two in the array

      if ($company == null) {
        $company = new User();
        $company->username = \Str::slug($ugandaCompany);
        $company->password = password_hash('password', PASSWORD_DEFAULT);
        $company->name = $ugandaCompany;
        $log_index = count($list_of_files) % $i;
        if ($log_index  < 2 || $log_index > 160) {
          $log_index = rand(2, count($list_of_files) - 2);
        }
        $logo = $list_of_files[$log_index];
        $company->company_logo = 'images/' . $logo;
        $company->avatar = 'images/user-' . rand(1, 50) . '.jpg';
        $now = new Carbon();
        $company->created_at = $now->subDays(rand(1, 400));
        $company->enterprise_id = 1;
        $names = explode(' ', $ugandaCompany);
        $company->first_name = $names[0];
        if (count($names) > 1) {
          $company->last_name = $names[1];
        } else {
          $company->last_name = 'Company';
        }
        $now = new Carbon();
        $company->date_of_birth = $now->subMonth(rand((12 * 14), (12 * 50)));
        $company->sex =  $sex[rand(0, 1)];
        $company->home_address = $faker->address();
        $company->current_address = $faker->address();
        $company->phone_number_1 = $faker->phoneNumber();
        $company->phone_number_2 = $faker->phoneNumber();
        $company->email = $faker->email();
        $company->nationality = 'Uganda';
        $company->religion = $religion[rand(0, 7)];
        $company->spouse_name = $faker->name();
        $company->spouse_phone = $faker->phoneNumber();
        $company->father_name = $faker->name();
        $company->father_phone = $faker->phoneNumber();
        $company->mother_name = $faker->name();
        $company->mother_phone = $faker->phoneNumber();
        $company->languages = $faker->sentence();
        $company->emergency_person_name = $faker->name();
        $company->emergency_person_phone = $faker->phoneNumber();
        $company->national_id_number = "CM" . $faker->randomNumber(6) . $faker->randomNumber(6);
        $company->passport_number = "UG-" . $faker->randomNumber(6);
        $company->nssf_number = "UG-" . $faker->randomNumber(6);
        $company->primary_school_name = $firstCompany->primary_school_name;
        $company->primary_school_name = $firstCompany->primary_school_name;
        $company->seconday_school_name = $firstCompany->seconday_school_name;
        $company->high_school_name = $firstCompany->high_school_name;
        $company->degree_university_name = $firstCompany->degree_university_name;
        $company->degree_university_year_graduated = $firstCompany->degree_university_year_graduated;
        $company->user_type = $firstCompany->user_type;
        $company->masters_university_name = $firstCompany->masters_university_name;
        $company->user_batch_importer_id = 1;
        $company->verification = 1;
        $company->status = 1;
        $company->marital_status = [
          'Single',
          'Married',
          'Widowed',
        ][rand(0, 2)];
        $company->title = [
          'Mr',
          'Mr',
          'Mr',
          'Mrs',
          'Miss',
          'Prof',
          'Dr',
        ][rand(0, 5)];
        shuffle($objectives);
        shuffle($objectives);
        shuffle($special_qualifications);
        shuffle($special_qualifications);
        $company->objective =  $objectives[2];
        $company->special_qualification =  $special_qualifications[2];

        $company->present_salary = [
          'UGX 1,000,000',
          'UGX 1,200,000',
          'UGX 1,500,000',
          'UGX 1,800,000',
          'UGX 2,000,000',
          'UGX 2,200,000',
          'UGX 2,500,000',
          'UGX 2,800,000',
          'UGX 3,000,000',
          'UGX 3,200,000',
          'UGX 3,500,000',
          'UGX 3,800,000',
          'UGX 4,000,000',
          'UGX 4,200,000',
          'UGX 4,500,000',
          'UGX 4,800,000',
          'UGX 5,000,000',
          'UGX 5,200,000',
          'UGX 5,500,000',
          'UGX 5,800,000'
        ][rand(0, 19)];

        $company->expected_salary = [
          'UGX 1,000,000',
          'UGX 1,200,000',
          'UGX 1,500,000',
          'UGX 1,800,000',
          'UGX 2,000,000',
          'UGX 2,200,000',
          'UGX 2,500,000',
          'UGX 2,800,000',
          'UGX 3,000,000',
          'UGX 3,200,000',
          'UGX 3,500,000',
          'UGX 3,800,000',
          'UGX 4,000,000',
          'UGX 4,200,000',
          'UGX 4,500,000',
          'UGX 4,800,000',
          'UGX 5,000,000',
          'UGX 5,200,000',
          'UGX 5,500,000',
          'UGX 5,800,000'
        ][rand(0, 19)];
        shuffle($career_summaries);
        shuffle($career_summaries);
        $company->career_summary = $career_summaries[2];
        $company->expected_job_level =
          [
            'Entry Level',
            'Mid Level',
            'Senior Level',
            'Manager',
            'Director',
            'Vice President',
            'President',
            'C-Level',
            'Executive',
            'Intern'
          ][rand(0, 9)];
        $company->expected_job_nature =
          [
            'Full-time',
            'Part-time',
            'Contract',
            'Temporary',
            'Internship',
            'Freelance',
            'Remote',
            'On-site',
            'Shift Work',
            'Volunteer'
          ][rand(0, 9)];
        $company->preferred_job_location =
          [
            'Kampala',
            'Entebbe',
            'Jinja',
            'Mbarara',
            'Gulu',
            'Mbale',
            'Fort Portal',
            'Lira',
            'Arua',
            'Soroti',
            'Hoima',
            'Masaka',
            'Mukono',
            'Kasese',
            'Kabale',
            'Tororo',
            'Iganga',
            'Mityana',
            'Luwero',
            'Nakasongola'
          ][rand(0, 19)];
        $company->preferred_job_category =
          [
            'Technology',
            'Finance',
            'Healthcare',
            'Education',
            'Manufacturing',
            'Retail',
            'Construction',
            'Transportation',
            'Hospitality',
            'Agriculture'
          ][rand(0, 9)];
        // $company->
        $company->preferred_job_category_other = $company->preferred_job_category;
        $company->preferred_job_districts =    $company->preferred_job_location;
        $company->preferred_job_abroad =    ['Yes', 'No'][rand(0, 1)];
        $company->has_disability =    ['Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->is_registered_on_disability =    ['Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->dificulty_to_see =    ['Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->company_has_accessibility =    ['Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->preferred_job_countries =   [
          'Uganda,Kenya, Tanzania',
          'Dubai, Saudi',
          'USA, Canada, UK',
          'Australia, New Zealand',
          'South Africa, Nigeria',
          'India, China, Japan',
          'Germany, France, Italy',
          'Brazil, Argentina, Chile',
          'Russia, Ukraine, Poland',
          'Mexico, Colombia, Peru',
          'South Korea, Singapore, Malaysia',
          'Sweden, Norway, Denmark'
        ][rand(0, 10)];
        $company->disability_type =   [
          "Visual impairment",
          "Hearing impairment",
          "Mobility impairment",
          "Cognitive impairment",
          "Speech impairment",
          "Psychiatric disability",
          "Intellectual disability",
          "Learning disability",
          "Chronic illness",
          "Other"
        ][rand(0, 9)];
        $company->dificulty_to_see =    ['Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->dificulty_to_hear =    ['Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->dificulty_to_walk =    ['Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->dificulty_to_speak =    ['Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->company_has_disability_inclusion_policy =    ['Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'][rand(0, 5)];
        $company->dificulty_display_on_cv =    ['Yes',   'No'][rand(0, 1)];
        $company->country_code =    ['+256',   '+254'][rand(0, 1)];
        $company->blood_group = [
          'A+',
          'A-',
          'B+',
          'B-',
          'AB+',
          'AB-',
          'O+',
          'O-'
        ][rand(0, 7)];
        $company->height = rand(15, 30);
        $company->weight = rand(35, 90);
        $company->company_name = $ugandaCompany;
        $now = Carbon::now();
        $company->company_year_of_establishment = $now->subMonth(rand(10, (24 * 30)));
        $company->company_employees_range = [
          '1-10',
          '11-50',
          '51-200',
          '201-500',
          '501-1000',
          '1001-5000',
          '5001-10000',
          '10001+'
        ][rand(0, 7)];
        $company->company_country = 'Uganda';
        $company->company_address = $faker->address();
        $company->company_phone_number = $faker->phonenumber();
        $company->company__phone = $faker->phonenumber();
        $company->company_website_url = $faker->url();
        $company->company_linkedin_url = $faker->url();
        $company->company_facebook_url = $faker->url();
        $company->company__email = $faker->email();
        $company->company_district_id = rand(1, 117);
        $company->company_trade_license_no = rand(1, 117);
        $company->company_tax_id = rand(1, 117);
        $company->company_main_category_id = rand(1, 30);
        $company->company_description = [
          "A leading company in the industry with a commitment to excellence and innovation.",
          "A dynamic organization known for its exceptional customer service and quality products.",
          "A reputable company with a strong focus on sustainability and community engagement.",
          "A forward-thinking company that leverages cutting-edge technology to drive growth.",
          "A trusted name in the market, delivering reliable and efficient solutions.",
          "A company dedicated to fostering a positive work environment and employee development.",
          "A market leader with a proven track record of success and industry expertise.",
          "A company that values integrity, transparency, and ethical business practices.",
          "A customer-centric organization that prioritizes client satisfaction and loyalty.",
          "A company with a diverse portfolio of products and services, catering to various industries."
        ][rand(0, 9)];

        $company->company_operating_hours = json_encode([
          'Monday' => '8:00 AM - 5:00 PM',
          'Tuesday' => '8:00 AM - 5:00 PM',
          'Wednesday' => '8:00 AM - 5:00 PM',
          'Thursday' => '8:00 AM - 5:00 PM',
          'Friday' => '8:00 AM - 5:00 PM',
          'Saturday' => '9:00 AM - 1:00 PM',
          'Sunday' => 'Closed'
        ]);
        $company->company_certifications = json_encode([
          "ISO 9001:2015",
          "ISO 14001:2015",
          "OHSAS 18001:2007",
          "ISO 45001:2018",
          "ISO 22000:2018",
          "ISO 27001:2013",
          "ISO 50001:2018",
          "ISO 31000:2018",
          "ISO 22301:2019",
          "ISO 20000-1:2018"
        ]);
        //company_ownership_type
        $company->company_ownership_type = [
          'Private',
          'Public',
          'Government',
          'Non-Profit',
          'Partnership',
          'Cooperative',
          'Sole Proprietorship',
          'Limited Liability',
          'Joint Venture',
          'Franchise'
        ][rand(0, 9)];
        $company->company_status = 'Active';
        $company->is_company = 'Yes';

        echo $i . ". " . $ugandaCompany . ' NEW <br>';
        $company->save();
      } else {
        echo $company->name . ".  exists: <br>";
      }
    }

    $companies = [];
  }

  // ===================================
  // BLOG API ENDPOINTS
  // ===================================

  /**
   * Get blog posts with pagination and filtering
   * 
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function blog_posts(Request $request)
  {
    try {
      // Start building query
      $query = \App\Models\BlogPost::published();

      // Apply search filter
      if ($request->filled('search')) {
        $search = $request->input('search');
        $query->search($search);
      }

      // Apply category filter
      if ($request->filled('category')) {
        $category = $request->input('category');
        $query->byCategory($category);
      }

      // Apply tag filter
      if ($request->filled('tag')) {
        $tag = $request->input('tag');
        $query->byTag($tag);
      }

      // Apply featured filter
      if ($request->filled('featured') && $request->boolean('featured')) {
        $query->featured();
      }

      // Sorting
      $sortBy = $request->input('sort_by', 'published_at');
      $sortOrder = $request->input('sort_order', 'desc');
      
      if (in_array($sortBy, ['published_at', 'views_count', 'likes_count', 'title'])) {
        $query->orderBy($sortBy, $sortOrder);
      } else {
        $query->orderBy('published_at', 'desc');
      }

      // Paginate results
      $perPage = $request->input('per_page', 20);
      $perPage = min(50, max(1, (int)$perPage)); // Limit between 1-50
      
      $posts = $query->paginate($perPage);

      // Transform posts to include computed fields
      $posts->getCollection()->transform(function ($post) {
        return [
          'id' => $post->id,
          'title' => $post->title,
          'slug' => $post->slug,
          'excerpt' => $post->excerpt,
          'content' => $post->content,
          'featured_image' => $post->featured_image,
          'featured_image_url' => $post->featured_image_url,
          'author_name' => $post->author_name,
          'author_email' => $post->author_email,
          'status' => $post->status,
          'category' => $post->category,
          'tags' => $post->tags,
          'views_count' => $post->views_count,
          'likes_count' => $post->likes_count,
          'featured' => $post->featured,
          'published_at' => $post->published_at,
          'formatted_published_at' => $post->formatted_published_at,
          'reading_time_minutes' => $post->reading_time_minutes,
          'reading_time_text' => $post->reading_time_text,
          'url' => $post->getUrl(),
          'created_at' => $post->created_at,
          'updated_at' => $post->updated_at,
        ];
      });

      return $this->success($posts, 'Blog posts retrieved successfully');

    } catch (\Exception $e) {
      \Log::error('Blog posts fetch error: ' . $e->getMessage());
      return $this->error('Failed to fetch blog posts');
    }
  }

  /**
   * Get single blog post by slug
   * 
   * @param Request $request
   * @param string $slug
   * @return \Illuminate\Http\JsonResponse
   */
  public function blog_post_single(Request $request, $slug)
  {
    try {
      $post = \App\Models\BlogPost::published()
        ->where('slug', $slug)
        ->first();

      if (!$post) {
        return $this->error('Blog post not found', 404);
      }

      // Format response
      $postData = [
        'id' => $post->id,
        'title' => $post->title,
        'slug' => $post->slug,
        'excerpt' => $post->excerpt,
        'content' => $post->content,
        'featured_image' => $post->featured_image,
        'featured_image_url' => $post->featured_image_url,
        'author_name' => $post->author_name,
        'author_email' => $post->author_email,
        'status' => $post->status,
        'category' => $post->category,
        'tags' => $post->tags,
        'views_count' => $post->views_count,
        'likes_count' => $post->likes_count,
        'featured' => $post->featured,
        'published_at' => $post->published_at,
        'formatted_published_at' => $post->formatted_published_at,
        'reading_time_minutes' => $post->reading_time_minutes,
        'reading_time_text' => $post->reading_time_text,
        'meta_description' => $post->meta_description,
        'meta_keywords' => $post->meta_keywords,
        'url' => $post->getUrl(),
        'created_at' => $post->created_at,
        'updated_at' => $post->updated_at,
      ];

      return $this->success($postData, 'Blog post retrieved successfully');

    } catch (\Exception $e) {
      \Log::error('Blog post fetch error: ' . $e->getMessage());
      return $this->error('Failed to fetch blog post');
    }
  }

  /**
   * Get blog categories
   * 
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function blog_categories(Request $request)
  {
    try {
      $categories = \App\Models\BlogPost::getCategories();
      return $this->success($categories, 'Blog categories retrieved successfully');
    } catch (\Exception $e) {
      \Log::error('Blog categories fetch error: ' . $e->getMessage());
      return $this->error('Failed to fetch blog categories');
    }
  }

  /**
   * Get blog tags
   * 
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function blog_tags(Request $request)
  {
    try {
      $tags = \App\Models\BlogPost::getAllTags();
      return $this->success($tags, 'Blog tags retrieved successfully');
    } catch (\Exception $e) {
      \Log::error('Blog tags fetch error: ' . $e->getMessage());
      return $this->error('Failed to fetch blog tags');
    }
  }

  /**
   * Record blog post view
   * 
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function blog_post_view(Request $request, $id)
  {
    try {
      $post = \App\Models\BlogPost::find($id);
      
      if (!$post) {
        return $this->error('Blog post not found', 404);
      }

      $post->incrementViews();

      return $this->success([
        'views_count' => $post->views_count,
        'message' => 'View recorded successfully'
      ]);

    } catch (\Exception $e) {
      \Log::error('Blog post view recording error: ' . $e->getMessage());
      return $this->error('Failed to record view');
    }
  }

  /**
   * Like blog post
   * 
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function blog_post_like(Request $request, $id)
  {
    try {
      $post = \App\Models\BlogPost::find($id);
      
      if (!$post) {
        return $this->error('Blog post not found', 404);
      }

      $post->incrementLikes();

      return $this->success([
        'likes_count' => $post->likes_count,
        'message' => 'Like recorded successfully'
      ]);

    } catch (\Exception $e) {
      \Log::error('Blog post like recording error: ' . $e->getMessage());
      return $this->error('Failed to record like');
    }
  }
}
