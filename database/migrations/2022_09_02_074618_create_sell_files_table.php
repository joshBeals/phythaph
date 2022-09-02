<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sell_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sell_id')->nullable();
            $table->unsignedBigInteger('file_id');
            $table->timestamps();

            $table->foreign('sell_id')->references('id')->on('user_sells')->onDelete('set null');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sell_files');
    }
}
