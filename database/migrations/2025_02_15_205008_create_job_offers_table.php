<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('job_title')->nullable();
            $table->text('company_name')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->text('job_description')->nullable();
            $table->foreignIdFor(\App\Models\User::class, 'candidate_id')->nullable();
            $table->foreignIdFor(\App\Models\User::class, 'company_id')->nullable();
            $table->string('status')->default('Pending');
            $table->text('candidate_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_offers');
    }
}
