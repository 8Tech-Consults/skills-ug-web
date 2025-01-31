<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('title')->nullable()->comment('Job title');
            $table->foreignIdFor(\App\Models\User::class, 'posted_by_id')->nullable()->comment('User who posted the job');
            $table->string('status')->nullable()->default('Pending')->comment('Job status');
            $table->text('deadline')->nullable()->comment('Application deadline');
            $table->text('category_id')->nullable()->comment('Job category');
            $table->text('district_id')->nullable()->comment('District ID');
            $table->text('sub_county_id')->nullable()->comment('Sub-county ID');
            $table->text('address')->nullable()->comment('Job address');
            $table->integer('vacancies_count')->nullable()->comment('Number of vacancies');
            $table->string('employment_status')->nullable()->default('Full Time')->comment('Full Time, Part Time, Contract, Internship');
            $table->text('workplace')->nullable()->default('Onsite')->comment('Onsite or Remote');
            $table->text('responsibilities')->nullable()->comment('Key responsibilities');
            $table->text('experience_field')->nullable()->comment('Relevant experience field');
            $table->text('experience_period')->nullable()->comment('Experience duration');
            $table->string('show_salary')->nullable()->default('Yes')->comment('Display salary info');
            $table->integer('minimum_salary')->nullable()->comment('Minimum salary range');
            $table->integer('maximum_salary')->nullable()->comment('Maximum salary range');
            $table->text('benefits')->nullable()->comment('Job benefits');
            $table->text('job_icon')->nullable()->comment('Icon or image path');
            $table->text('gender')->nullable()->comment('Gender requirement');
            $table->text('min_age')->nullable()->comment('Minimum age requirement');
            $table->text('max_age')->nullable()->comment('Maximum age requirement');
            $table->string('required_video_cv')->nullable()->default('No')->comment('Need a video CV?');
            $table->text('minimum_academic_qualification')->nullable()->comment('Academic requirement');
            $table->string('application_method')->nullable()->comment('Method of application');
            $table->text('application_method_details')->nullable()->comment('Application details');
            $table->text('slug')->nullable()->comment('SEO-friendly URL slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
