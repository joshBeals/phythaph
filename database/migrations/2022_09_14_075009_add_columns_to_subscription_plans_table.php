<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToSubscriptionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('min_balance', 10, 2)->nullable()->after('renewal_fee');
            $table->decimal('max_balance', 10, 2)->nullable()->after('min_balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('min_balance');
            $table->dropColumn('max_balance');
        });
    }
}
