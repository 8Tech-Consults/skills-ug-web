<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreToJobWebSites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_web_sites', function (Blueprint $table) {
            $table->date('last_fetched_at')->nullable();
            $table->integer('page_number')->nullable()->default(1);
            $table->integer('total_posts_found')->nullable()->default(0);
            $table->integer('new_posts_found')->nullable()->default(0);
            $table->string('status')->nullable()->default('active');
            $table->string('fetch_status')->nullable();
            $table->text('failed_message')->nullable();
            $table->longText('response_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_web_sites', function (Blueprint $table) {
            //
        });
    }
}
