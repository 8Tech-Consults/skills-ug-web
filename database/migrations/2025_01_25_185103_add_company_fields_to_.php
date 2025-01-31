<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyFieldsTo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            // From the registration form
            $table->text('company_name')->nullable();
            $table->text('company_year_of_establishment')->nullable();
            $table->text('company_employees_range')->nullable();
            $table->text('company_country')->nullable();
            $table->text('company_address')->nullable();
            $table->text('company_district_id')->nullable();
            $table->text('company_sub_county_id')->nullable();
            $table->text('company_main_category_id')->nullable();
            $table->text('company_sub_category_id')->nullable();
            $table->text('company_phone_number')->nullable();
            $table->text('company_description')->nullable();
            $table->text('company_trade_license_no')->nullable();
            $table->text('company_website_url')->nullable();
            $table->text('company__email')->nullable();
            $table->text('company__phone')->nullable();
            $table->string('company_has_accessibility')->nullable()->default('No');
            $table->text('company_has_disability_inclusion_policy')->nullable();
            $table->text('company_logo')->nullable();
            $table->text('company_tax_id')->nullable();
            $table->text('company_facebook_url')->nullable();
            $table->text('company_linkedin_url')->nullable();
            $table->text('company_operating_hours')->nullable();
            $table->text('company_certifications')->nullable();
            $table->text('company_ownership_type')->nullable();
            $table->text('company_status')->nullable();
            $table->string('is_company')->nullable()->default('No');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            //
        });
    }
}
