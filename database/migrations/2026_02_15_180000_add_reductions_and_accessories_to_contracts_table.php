<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('reduction_bns', 5, 2)->default(0)->after('reduction_amount')->comment('Réduction BNS Bonus/Malus (0-100 %)');
            $table->decimal('reduction_on_commission', 5, 2)->default(0)->after('reduction_bns')->comment('Réduction sur commission (0-100 %)');
            $table->decimal('reduction_on_profession_percent', 5, 2)->nullable()->after('reduction_on_commission')->comment('Réduction profession en % (0-100)');
            $table->unsignedInteger('reduction_on_profession_amount')->nullable()->after('reduction_on_profession_percent')->comment('Réduction profession en FCFA');
            $table->unsignedInteger('company_accessory')->default(0)->after('accessory_amount')->comment('Accessoire compagnie (FCFA)');
            $table->unsignedInteger('agency_accessory')->default(0)->after('company_accessory')->comment('Accessoire agence (FCFA)');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'reduction_bns',
                'reduction_on_commission',
                'reduction_on_profession_percent',
                'reduction_on_profession_amount',
                'company_accessory',
                'agency_accessory',
            ]);
        });
    }
};
