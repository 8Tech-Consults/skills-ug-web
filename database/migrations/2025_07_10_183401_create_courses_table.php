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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('detailed_description')->nullable();
            $table->string('instructor_name')->nullable();
            $table->text('instructor_bio')->nullable();
            $table->string('instructor_avatar')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('preview_video')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency')->default('UGX');
            $table->integer('duration_hours')->default(0);
            $table->string('difficulty_level')->default('Beginner');
            $table->string('language')->default('English');
            $table->text('requirements')->nullable();
            $table->text('what_you_learn')->nullable();
            $table->text('tags')->nullable();
            $table->string('status')->default('active');
            $table->string('featured')->default('No');
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('enrollment_count')->default(0);
            $table->timestamps();
            
            $table->index(['status', 'featured']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
};