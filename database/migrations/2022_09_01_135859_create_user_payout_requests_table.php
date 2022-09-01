<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPayoutRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('transaction_id')->nullable()->unique();

            $table->decimal('amount', 15, 4);
            $table->decimal('penalty', 15, 4)->nullable();
            $table->decimal('disburse_amount', 15, 4)->nullable();

            $table->enum('status', array_keys(\App\Models\UserPayoutRequest::STATUSES))->default('pending');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->mediumText('note')->nullable();

            $table->enum('source', array_keys(\App\Models\UserPayoutRequest::SOURCES))->default('wallet');

            $table->string('wallet')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();

            $table->dateTime('disbursed_at')->nullable();
            $table->boolean('pending_disbursal')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('admins')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('bank_id')->references('id')->on('user_banks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_payout_requests');
    }
}
