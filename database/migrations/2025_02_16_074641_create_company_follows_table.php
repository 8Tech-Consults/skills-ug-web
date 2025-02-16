<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyFollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //create if not existt
        if (!Schema::hasTable('company_follows')) {
            Schema::create('company_follows', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->foreignIdFor(User::class,'user_id');
                $table->foreignIdFor(User::class,'company_id'); 
            });
        } 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_follows');
    }
}
