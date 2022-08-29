<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->enum('account_type', \App\Models\UserWallet::ACCOUNT_TYPES);
            $table->double('balance', 15, 4)->default(0);

            $table->timestamp('last_deposit_at')->nullable();
            $table->timestamp('last_withdrawal_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

        });

        Schema::create('user_wallet_balance_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['deposit', 'withdrawal', 'others']);
            $table->unsignedBigInteger('transaction_id')->nullable()->unique();

            $table->decimal('previous_balance', 15, 4)->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->decimal('new_balance', 15, 4)->nullable();

            $table->mediumText('description')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('user_wallets')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_wallet_balance_histories');
        Schema::dropIfExists('user_wallets');
    }
}
