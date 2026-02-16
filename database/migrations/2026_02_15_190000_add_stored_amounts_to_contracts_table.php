<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedInteger('prime_ttc')->nullable()->after('agency_accessory')->comment('Prime TTC (grille + accessoires comp/agence)');
            $table->unsignedInteger('commission_amount')->default(0)->after('prime_ttc')->comment('Commission totale (FCFA)');
            $table->unsignedInteger('reduction_bns_amount')->default(0)->after('reduction_on_profession_amount')->comment('Réduction BNS en FCFA (stockée)');
            $table->unsignedInteger('reduction_on_commission_amount')->default(0)->after('reduction_bns_amount')->comment('Réduction sur commission en FCFA (stockée)');
            $table->unsignedInteger('reduction_on_profession_amount_stored')->default(0)->after('reduction_on_commission_amount')->comment('Réduction profession en FCFA (stockée)');
            $table->unsignedInteger('total_reduction_amount')->default(0)->after('reduction_on_profession_amount_stored')->comment('Total réductions en FCFA');
            $table->unsignedInteger('total_amount')->nullable()->after('total_reduction_amount')->comment('Total final = prime_ttc + commission - réductions');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'prime_ttc',
                'commission_amount',
                'reduction_bns_amount',
                'reduction_on_commission_amount',
                'reduction_on_profession_amount_stored',
                'total_reduction_amount',
                'total_amount',
            ]);
        });
    }
};
