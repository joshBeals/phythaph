<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('account_number', 11)->nullable();
            $table->string('account_name')->nullable();
            $table->string('bvn', 12)->nullable()->unique();
            $table->string('bvn_name')->nullable();
            $table->string('recipient_code')->nullable()->unique()->description('default reciepient code');
            $table->timestamps();
            $table->softDeletes();
            $table->unique([
                'bank_code',
                'account_number',
                'account_name',
            ]);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_banks');
    }
}
