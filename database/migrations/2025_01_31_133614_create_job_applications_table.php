<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(\App\Models\User::class, 'applicant_id');
            $table->foreignIdFor(\App\Models\Job::class, 'job_id');
            $table->foreignIdFor(\App\Models\User::class, 'employer_id');
            $table->text('attachments')->nullable();
            $table->text('employer_message')->nullable();
            $table->text('applicant_message')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('status')->default('Pending'); // pending,interview,declined,hired
            $table->string('interview_email_sent')->default('No'); // Yes, No
            $table->string('hired_email_sent')->default('No'); // Yes, No
            $table->string('declinded_email_sent')->default('No'); // Yes, No
            $table->string('interview_scheduled_at')->nullable();
            $table->text('interview_location')->nullable(); // Can be physical or virtual
            $table->string('interview_type')->nullable();
            $table->string('interview_result')->nullable();
            $table->text('interviewer_notes')->nullable();
            $table->text('interviewer_rating')->nullable();
            $table->text('interviewee_feedback')->nullable();
            $table->text('interviewee_notes')->nullable();
            $table->text('interviewee_rating')->nullable();
            $table->text('contract_url')->nullable(); // If hired, link to contract
            $table->date('onboarding_start_date')->nullable();
            $table->text('onboarding_notes')->nullable();
            $table->text('additional_info')->nullable(); // Store custom JSON data if needed 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_applications');
    }
}
