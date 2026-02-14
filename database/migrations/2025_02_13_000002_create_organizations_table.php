<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('employee_count_range', 20)->nullable(); // 1-10, 11-50, 51-200, 201+
            $table->string('referral_source', 50)->nullable(); // google, bouche_a_oreille, reseaux_sociaux, recommandation, pub, autre
            $table->string('industry', 50)->nullable(); // cabinet, ecole, ong, sante, commerce, autre
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
