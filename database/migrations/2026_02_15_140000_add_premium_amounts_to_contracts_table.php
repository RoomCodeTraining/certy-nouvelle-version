<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedInteger('base_amount')->nullable()->after('metadata');
            $table->unsignedInteger('rc_amount')->nullable()->after('base_amount');
            $table->unsignedInteger('defence_appeal_amount')->nullable()->after('rc_amount');
            $table->unsignedInteger('person_transport_amount')->nullable()->after('defence_appeal_amount');
            $table->unsignedInteger('accessory_amount')->nullable()->after('person_transport_amount');
            $table->unsignedInteger('taxes_amount')->nullable()->after('accessory_amount');
            $table->unsignedInteger('cedeao_amount')->nullable()->after('taxes_amount');
            $table->unsignedInteger('fga_amount')->nullable()->after('cedeao_amount');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'base_amount', 'rc_amount', 'defence_appeal_amount', 'person_transport_amount',
                'accessory_amount', 'taxes_amount', 'cedeao_amount', 'fga_amount',
            ]);
        });
    }
};
