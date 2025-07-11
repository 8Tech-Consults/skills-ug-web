<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_reviews', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Service::class);
            $table->foreignIdFor(User::class, 'reviewer_id');
            $table->integer('rating')->unsigned(); // 1-5 rating
            $table->text('comment')->nullable();
            $table->string('status')->default('Active'); // Active, Hidden, Pending
            
            // Prevent duplicate reviews by same user for same service
            $table->unique(['service_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_reviews');
    }
}
