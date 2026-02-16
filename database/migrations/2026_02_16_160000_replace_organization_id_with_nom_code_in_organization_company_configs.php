<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });
        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'company_id']);
            $table->dropColumn(['organization_id', 'broker_code']);
            $table->string('name', 255)->nullable()->after('company_id');
            $table->string('code', 100)->nullable()->after('name');
        });

        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('organization_company_configs', function (Blueprint $table) {
            $table->dropUnique(['company_id']);
            $table->dropColumn(['name', 'code']);
            $table->foreignId('organization_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('broker_code', 100)->nullable()->after('policy_number_identifier');
            $table->unique(['organization_id', 'company_id']);
        });
    }
};
