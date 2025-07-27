<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodToCourseSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_currency');
            $table->string('payment_date')->nullable()->after('payment_method');
            $table->string('currency')->nullable()->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_date', 'currency']);
        });
    }
}
