<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUserPawnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_pawns', function (Blueprint $table) {
            $table->dropColumn('item_images');
            $table->enum('status', ['submitted', 'pending', 'documents received', 'inspected', 'declined', 'approved'])->default('submitted')->nullable()->after('price');
        });
        
        DB::unprepared("ALTER TABLE user_pawns AUTO_INCREMENT = 100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_pawns', function (Blueprint $table) {
            $table->longtext('item_images')->nullable()->after('item_features');
            $table->dropColumn('status');
        });
    }
}
