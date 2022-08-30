<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionPlanToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pawn_master')->nullable()->default(0)->after('account_type');
            $table->unsignedBigInteger('plan_id')->nullable()->after('pawn_master');
            $table->unsignedBigInteger('subscription_id')->nullable()->after('plan_id');

            $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            $table->foreign('subscription_id')->references('id')->on('user_subscription_histories')->onDelete('set null');
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
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['subscription_id']);

            $table->dropColumn('plan_id');
            $table->dropColumn('subscription_id');
            $table->dropColumn('pawn_master');
        });
    }
}
