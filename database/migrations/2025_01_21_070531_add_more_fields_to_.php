<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMoreFieldsTo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('admin_users', function (Blueprint $table) {

            $table->engine = 'InnoDB';
            /**
             * Migration adds the following columns to the "admin_users" table:
             *  - objective
             *  - special_qualification
             *  - career_summary
             *  - present_salary
             *  - expected_salary
             *  - expected_job_level
             *  - expected_job_nature
             *  - preferred_job_location
             *  - preferred_job_category
             *  - preferred_job_category_other
             *  - preferred_job_districts
             *  - preferred_job_abroad
             *  - preferred_job_countries
             *  - has_disability
             *  - is_registered_on_disability
             *  - disability_type
             *  - dificulty_to_see
             *  - dificulty_to_hear
             *  - dificulty_to_walk
             *  - dificulty_to_speak
             *  - dificulty_display_on_cv
             *  - country_code
             *  - blood_group
             *  - height
             *  - weight
             */

            if (!Schema::hasColumn('admin_users', 'objective')) {
                $table->text('objective')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'special_qualification')) {
                $table->text('special_qualification')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'career_summary')) {
                $table->text('career_summary')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'present_salary')) {
                $table->text('present_salary')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'expected_salary')) {
                $table->text('expected_salary')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'expected_job_level')) {
                $table->text('expected_job_level')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'expected_job_nature')) {
                $table->text('expected_job_nature')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'preferred_job_location')) {
                $table->text('preferred_job_location')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'preferred_job_category')) {
                $table->text('preferred_job_category')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'preferred_job_category_other')) {
                $table->text('preferred_job_category_other')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'preferred_job_districts')) {
                $table->text('preferred_job_districts')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'preferred_job_abroad')) {
                $table->text('preferred_job_abroad')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'preferred_job_countries')) {
                $table->text('preferred_job_countries')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'has_disability')) {
                $table->text('has_disability')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'is_registered_on_disability')) {
                $table->text('is_registered_on_disability')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'disability_type')) {
                $table->text('disability_type')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'dificulty_to_see')) {
                $table->text('dificulty_to_see')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'dificulty_to_hear')) {
                $table->text('dificulty_to_hear')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'dificulty_to_walk')) {
                $table->text('dificulty_to_walk')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'dificulty_to_speak')) {
                $table->text('dificulty_to_speak')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'dificulty_display_on_cv')) {
                $table->text('dificulty_display_on_cv')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'country_code')) {
                $table->text('country_code')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'blood_group')) {
                $table->text('blood_group')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'height')) {
                $table->text('height')->nullable();
            }
            if (!Schema::hasColumn('admin_users', 'weight')) {
                $table->text('weight')->nullable();
            }

            DB::statement('ALTER TABLE admin_users ROW_FORMAT=DYNAMIC');
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
