<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            $table->string('gender', 20)->nullable();
            $table->date('birthday')->nullable();
            $table->string('phone', 20)->nullable();
            $table->mediumText('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();

            $table->boolean('is_active')->nullable()->default(0);
            $table->boolean('is_blocked')->nullable()->default(0);
            $table->tinyInteger("must_change_password")->default(0)->nullable();
            $table->string('referral_code')->nullable();
            $table->unsignedInteger('referred_by')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Set the user increment to high number
        DB::unprepared("ALTER TABLE users AUTO_INCREMENT = 290800;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
