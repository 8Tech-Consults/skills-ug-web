<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('type')->nullable();
            $table->text('status')->nullable();
            $table->text('start_location')->nullable();
            $table->text('end_location')->nullable();
            $table->text('start_time')->nullable();
            $table->text('end_time')->nullable();
            $table->text('driver_id')->nullable();
            $table->text('vehicle')->nullable();
            $table->text('items')->nullable();
            $table->text('distance')->nullable();
            $table->text('duration')->nullable();
            $table->text('amount')->nullable();
            $table->text('payment_status')->nullable();
            $table->text('payment_method')->nullable();
            $table->text('payment_date')->nullable();
            $table->text('code')->nullable();
            $table->text('code_verification')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trips');
    }
}
