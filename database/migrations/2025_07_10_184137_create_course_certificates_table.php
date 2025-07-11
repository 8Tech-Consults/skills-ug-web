<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('certificate_number')->unique();
            $table->timestamp('issued_date')->nullable();
            $table->timestamp('completion_date')->nullable();
            $table->decimal('grade', 5, 2)->default(0);
            $table->string('pdf_url')->nullable();
            $table->string('verification_code')->unique();
            $table->string('status')->default('active'); // active, revoked
            $table->timestamps();
            
            $table->unique(['user_id', 'course_id']);
            $table->index(['verification_code']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_certificates');
    }
};