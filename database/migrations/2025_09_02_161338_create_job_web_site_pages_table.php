<?php

use App\Models\JobWebSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobWebSitePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_web_site_pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(JobWebSite::class)->nullable();
            $table->text('title')->nullable();
            $table->text('content')->nullable();
            $table->text('url')->nullable();
            $table->string('status')->nullable()->default('pending');
            $table->string('post_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_web_site_pages');
    }
}
