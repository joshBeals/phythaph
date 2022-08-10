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
            $table->string('market_price_new')->nullable()->after('features');
            $table->string('market_price_imported')->nullable()->after('market_price_new');
            $table->string('market_price_local')->nullable()->after('market_price_imported');
            $table->string('market_price_computer_village')->nullable()->after('market_price_local');
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
