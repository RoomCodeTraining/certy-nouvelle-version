<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_company_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->string('code', 100)->nullable();
            $table->decimal('commission', 8, 2)->nullable()->comment('Commission % ou montant selon usage');
            $table->string('policy_number_identifier', 100)->nullable()->comment('Identifiant numéro de police');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_company_configs');
    }
};
