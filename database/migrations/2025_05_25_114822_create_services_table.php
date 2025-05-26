<?php

use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //if services exists, drop it
        if (Schema::hasTable('services')) {
            Schema::dropIfExists('services');
        }
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('title')->nullable();
            $table->text('slug')->nullable();
            $table->foreignIdFor(JobCategory::class)->nullable();
            $table->foreignIdFor(User::class, 'provider_id')->nullable(); // e.g. service provider or freelancer
            $table->string('status')->default('Active'); // e.g. Active, Inactive, Pending Approval 
            $table->text('tags')->nullable();                    // e.g. JSON or comma-separated

            // Descriptions
            $table->text('description')->nullable();
            $table->text('details')->nullable();              // bullet points


            // Pricing & Packages
            $table->decimal('price')->nullable();           // fixed/hourly/custom
            $table->text('price_description')->nullable(); // e.g. "starting at", "per hour"

            // Delivery & Timeline
            $table->text('delivery_time')->nullable();
            $table->text('delivery_time_description')->nullable(); // e.g. "standard delivery", "express delivery"

            // Requirements & Process
            $table->text('client_requirements')->nullable(); // e.g. "What we need from you"
            $table->text('process_description')->nullable(); // e.g. "How it works", "Steps to get started"

            // Media & Samples
            $table->text('cover_image')->nullable(); // URL to cover image
            $table->text('gallery')->nullable(); // e.g already done work
            $table->text('intro_video_url')->nullable(); // URL to intro video

            // Provider Info
            $table->text('provider_name')->nullable(); // e.g. "John Doe"
            $table->text('provider_logo')->nullable(); // URL to provider logo or profile picture
            $table->text('location')->nullable(); // e.g. "Kampala, Uganda"
            $table->text('languages_spoken')->nullable(); // e.g. list of languages
            $table->text('experience_years')->nullable(); // e.g. "5 years in graphic design"
            $table->text('certifications')->nullable(); // e.g. "Certified Graphic Designer, Adobe Certified Expert"

            // Policies
            $table->text('refund_policy')->nullable(); // e.g. "30-day money-back guarantee"

            // SEO & Marketing 
            $table->text('promotional_badge')->nullable(); // e.g. "Best Seller", "New Arrival" 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
}
