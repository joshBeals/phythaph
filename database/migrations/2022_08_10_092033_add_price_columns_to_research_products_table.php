<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceColumnsToResearchProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('research_products', function (Blueprint $table) {
            $table->double('market_price_new', 15, 2)->default(0)->nullable()->after('features');
            $table->double('market_price_imported', 15, 2)->default(0)->nullable()->after('market_price_new');
            $table->double('market_price_local', 15, 2)->default(0)->nullable()->after('market_price_imported');
            $table->double('market_price_computer_village', 15, 2)->default(0)->nullable()->after('market_price_local');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('research_products', function (Blueprint $table) {
            $table->dropColumn('market_price_new');
            $table->dropColumn('market_price_imported');
            $table->dropColumn('market_price_local');
            $table->dropColumn('market_price_computer_village');
        });
    }
}
