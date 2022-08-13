<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Set the user increment to high number
        DB::unprepared("ALTER TABLE categories AUTO_INCREMENT = 100100;");

        
        // Set the user increment to high number
        DB::unprepared("ALTER TABLE research_products AUTO_INCREMENT = 100100;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
