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
        Schema::create('course_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('subscription_type')->default('full'); // full, trial
            $table->string('status')->default('active'); // active, completed, cancelled, expired
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('payment_status')->default('pending'); // paid, pending, failed
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->string('payment_currency')->default('UGX');
            $table->timestamps();
            
            $table->index(['user_id', 'course_id']);
            $table->index(['user_id', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_subscriptions');
    }
};