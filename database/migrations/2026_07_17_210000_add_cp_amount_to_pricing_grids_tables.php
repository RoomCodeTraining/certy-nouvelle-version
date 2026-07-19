<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'vp_pricing_grids',
            'tpc_pricing_grids',
            'tpm_pricing_grids',
            'two_wheeler_pricing_grids',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'cp_amount')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedInteger('cp_amount')->default(0)->after('cedeao_amount');
            });
        }

        // Aligné sur CEDEAO : 1000 FCFA sur VP/TPC/TPM, 0 sur deux-roues
        foreach (['vp_pricing_grids', 'tpc_pricing_grids', 'tpm_pricing_grids'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'cp_amount')) {
                DB::table($table)->update(['cp_amount' => 1000]);
            }
        }
        if (Schema::hasTable('two_wheeler_pricing_grids') && Schema::hasColumn('two_wheeler_pricing_grids', 'cp_amount')) {
            DB::table('two_wheeler_pricing_grids')->update(['cp_amount' => 0]);
        }
    }

    public function down(): void
    {
        foreach ([
            'vp_pricing_grids',
            'tpc_pricing_grids',
            'tpm_pricing_grids',
            'two_wheeler_pricing_grids',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'cp_amount')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('cp_amount');
                });
            }
        }
    }
};
