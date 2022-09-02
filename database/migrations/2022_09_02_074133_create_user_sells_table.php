<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_sells', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->longtext('item_features')->nullable();
            $table->enum('type', ['new', 'pre-owned'])->default('new')->nullable();
            $table->string('price')->nullable();
            $table->enum('inspection_type', ['home', 'office'])->default('office')->nullable();
            $table->date('inspection_date')->nullable();
            $table->enum('status', ['submitted', 'pending', 'documents received', 'inspected', 'declined', 'approved'])->default('submitted')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
        
        DB::unprepared("ALTER TABLE user_sells AUTO_INCREMENT = 100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_sells');
    }
}
