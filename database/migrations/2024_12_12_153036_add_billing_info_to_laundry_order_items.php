<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillingInfoToLaundryOrderItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laundry_order_items', function (Blueprint $table) {
            $table->decimal('washing_amount', 10, 2)->nullable();
            $table->decimal('service_amount', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laundry_order_items', function (Blueprint $table) {
            //
        });
    }
}
