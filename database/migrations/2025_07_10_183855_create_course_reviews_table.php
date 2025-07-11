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
        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->integer('rating')->default(5);
            $table->text('review_text')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->string('status')->default('approved'); // approved, pending, rejected
            $table->timestamps();
            
            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'status']);
            $table->index(['course_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_reviews');
    }
};