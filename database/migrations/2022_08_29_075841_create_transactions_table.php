<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('reference')->nullable()->unique();
            $table->string('description')->nullable();
            $table->enum('type', \App\Models\Transaction::TYPES)->default('customer_charge')->nullable();
            $table->enum('status', ['pending', 'failed', 'success', 'queued', 'abandoned'])->nullable()->default('success');
            $table->text('payload')->nullable();

            // Disbursal
            $table->string('disbursal_type')->nullable();
            $table->unsignedBigInteger('disbursal_entity_id')->nullable();

            $table->decimal('amount', 15, 2)->nullable()->description('In Kobo');
            $table->string('payment_method')->default('processor')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('paid_via')->nullable();
            $table->string('currency', 10)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('customer_code', 100)->nullable();
            $table->decimal('processor_charge', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
