<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('organization_company_configs', 'nom') && ! Schema::hasColumn('organization_company_configs', 'name')) {
            Schema::table('organization_company_configs', function (Blueprint $table) {
                $table->renameColumn('nom', 'name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('organization_company_configs', 'name') && ! Schema::hasColumn('organization_company_configs', 'nom')) {
            Schema::table('organization_company_configs', function (Blueprint $table) {
                $table->renameColumn('name', 'nom');
            });
        }
    }
};
