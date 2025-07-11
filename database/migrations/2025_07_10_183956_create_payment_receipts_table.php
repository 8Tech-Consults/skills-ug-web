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
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('receipt_number')->unique();
            $table->string('payment_method'); // mobile_money, card, bank_transfer
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('UGX');
            $table->string('transaction_id')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->string('pdf_url')->nullable();
            $table->string('status')->default('pending'); // success, failed, pending
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['transaction_id']);
            $table->index(['payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_receipts');
    }
};