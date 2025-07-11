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
        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('time_spent_seconds')->default(0);
            $table->string('completed')->default('No');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'material_id']);
            $table->index(['user_id', 'course_id']);
            $table->index(['user_id', 'completed']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_progress');
    }
};