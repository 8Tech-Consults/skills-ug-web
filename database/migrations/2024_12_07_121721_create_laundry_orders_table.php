<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaundryOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //delete laundry_orders
        Schema::dropIfExists('laundry_orders');
        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(User::class, 'user_id');
            

            // Step 1: Customer Order info
            $table->text('customer_name')->nullable();
            $table->text('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->text('pickup_address')->nullable();
            $table->text('pickup_gps')->nullable();
            $table->text('delivery_address')->nullable();

            $table->text('special_instructions')->nullable();

            // Step 2: App Bills
            $table->decimal('total_amount', 10, 2)->nullable();

            // Step 3: Customer Payment
            $table->string('payment_status')->default('Unpaid')->nullable();
            $table->text('payment_method')->nullable();
            $table->text('payment_date')->nullable();
            $table->text('stripe_payment_link')->nullable();
            $table->text('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->text('customer_photos')->nullable();

            // Step 4: Front Desk Scheduling
            $table->timestamp('scheduled_pickup_time')->nullable();
            $table->unsignedBigInteger('assigned_driver_id')->nullable();
            $table->foreignIdFor(User::class, 'driver_id')->nullable();

            // Step 5: Driver Pickup
            $table->timestamp('actual_pickup_time')->nullable();
            $table->text('pickup_notes')->nullable();

            // Step 6: Driver Delivery to Laundry
            $table->timestamp('laundry_delivery_time')->nullable();

            // Step 7: Front Desk Assignment to Washer
            $table->timestamp('washer_assignment_time')->nullable();
            $table->unsignedBigInteger('assigned_washer_id')->nullable();
            $table->foreignIdFor(User::class, 'washer_id')->nullable();

            // Step 8: Washer Updates
            $table->timestamp('washing_start_time')->nullable();
            $table->timestamp('washing_end_time')->nullable();
            $table->timestamp('drying_start_time')->nullable();
            $table->timestamp('drying_end_time')->nullable();

            // Step 9: Front Desk Scheduling Delivery
            $table->timestamp('scheduled_delivery_time')->nullable(); 
            $table->foreignIdFor(User::class, 'delivery_driver_id')->nullable();

            // Step 10: Driver Delivery to Customer
            $table->timestamp('actual_delivery_time')->nullable();
            $table->text('delivery_notes')->nullable();

            // Step 11: Customer Payment (if not paid earlier)
            $table->timestamp('final_payment_date')->nullable();

            // Step 12: Customer Receipt Approval
            $table->timestamp('receipt_approved_date')->nullable();

            // Step 13-14: Customer Ratings and Feedback
            $table->integer('rating')->nullable();
            $table->integer('driver_amount')->nullable();
            $table->integer('driving_distance')->nullable();
            $table->text('feedback')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laundry_orders');
    }
}
