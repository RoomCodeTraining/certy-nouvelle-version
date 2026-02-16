<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('organization_company_configs', 'company_id')) {
            return;
        }

        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->dropUnique(['company_id']);
            $table->dropColumn('company_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('organization_company_configs', 'company_id')) {
            return;
        }

        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique('company_id');
        });
    }
};
