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
        Schema::create('course_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('type'); // new_course, course_update, reminder, certificate, quiz_result, subscription_expiry
            $table->string('title');
            $table->text('message');
            $table->string('read_status')->default('unread');
            $table->string('action_url')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'read_status']);
            $table->index(['user_id', 'type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_notifications');
    }
};