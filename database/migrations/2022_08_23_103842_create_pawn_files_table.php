<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePawnFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pawn_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pawn_id')->nullable();
            $table->unsignedBigInteger('file_id');
            $table->timestamps();

            $table->foreign('pawn_id')->references('id')->on('user_pawns')->onDelete('set null');
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
        Schema::dropIfExists('pawn_files');
    }
}
