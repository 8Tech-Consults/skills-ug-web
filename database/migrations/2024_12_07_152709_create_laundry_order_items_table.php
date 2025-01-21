<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaundryOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laundry_order_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->nullable();
            $table->text('type')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('customer_photos')->nullable();
            $table->text('quantity')->nullable();
            $table->text('price')->nullable();
            $table->text('total')->nullable();
            $table->text('order_id')->nullable();
            $table->text('order_number')->nullable();
            $table->string('status')->default('Pending')->nullable();
            $table->text('washer_id')->nullable();
            $table->text('washer_name')->nullable();
            $table->text('washer_notes')->nullable();
            $table->text('washer_photos')->nullable();    
            $table->text('local_id')->nullable();    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laundry_order_items');
    }
}
