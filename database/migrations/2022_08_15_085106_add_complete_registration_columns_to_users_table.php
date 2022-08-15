<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompleteRegistrationColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('house_number')->nullable()->after('address');
            $table->string('street')->nullable()->after('house_number');
            $table->string('lcda')->nullable()->after('street');
            $table->string('lga')->nullable()->after('lcda');
            $table->string('company_name')->nullable()->after('address');
            $table->string('company_phone')->nullable()->after('company_name');
            $table->string('rc_number')->nullable()->after('company_phone');
            $table->string('country')->nullable()->after('rc_number');
            $table->string('postal_code')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('house_number');
            $table->dropColumn('street');
            $table->dropColumn('lcda');     
            $table->dropColumn('lga');  
            $table->dropColumn('company_name');         
            $table->dropColumn('company_phone');
            $table->dropColumn('rc_number');
            $table->dropColumn('country');       
            $table->dropColumn('postal_code');
        });
    }
}
