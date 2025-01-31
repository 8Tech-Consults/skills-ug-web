<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->nullable()->comment('Category name')->unique();
            $table->text('description')->nullable()->comment('Category description');
            $table->string('type')->nullable()->default('Functional')->comment('Category type: Functional, Industry');
            $table->text('icon')->nullable()->comment('Category icon');
            $table->text('slug')->nullable()->comment('SEO-friendly URL slug');
            $table->text('status')->nullable()->default('Active')->comment('Category status: Active, Inactive, Deleted');
            $table->integer('jobs_count')->nullable()->comment('Number of jobs in this category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_categories');
    }
}
