<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBankRecipientCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_bank_recipient_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->unsignedBigInteger('user_id');

            $table->string('payment_processor')->default('paystack');
            $table->string('account')->default('default');
            $table->string('recipient_code');

            $table->timestamps();

            $table->unique([
                'bank_id',
                'user_id',
                'payment_processor',
                'account',
                'recipient_code',
            ], "unique_for_user_bank");

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
        Schema::dropIfExists('user_bank_recipient_codes');
    }
}
