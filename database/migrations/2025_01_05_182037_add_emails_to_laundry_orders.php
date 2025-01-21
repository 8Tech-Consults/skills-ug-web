<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailsToLaundryOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            $table->string('order_received_email_sent')->default('No')->nullable();
            $table->string('order_picked_up_email_sent')->default('No')->nullable();
            $table->string('order_ready_for_payment_email_sent')->default('No')->nullable();
            $table->string('order_receipt_email_sent')->default('No')->nullable();
            $table->string('order_washed_email_sent')->default('No')->nullable();
            $table->string('order_delivered_email_sent')->default('No')->nullable();
            $table->string('order_feedback_email_sent')->default('No')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            //
        });
    }
}
