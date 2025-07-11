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
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('questions'); // JSON array of questions
            $table->integer('passing_score')->default(70);
            $table->integer('time_limit_minutes')->default(30);
            $table->integer('max_attempts')->default(3);
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->index(['unit_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_quizzes');
    }
};