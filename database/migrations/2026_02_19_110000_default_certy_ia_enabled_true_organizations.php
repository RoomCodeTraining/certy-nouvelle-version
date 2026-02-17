<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Active Certy IA pour toutes les organisations existantes.
     * Quand CERTY_IA_ENABLED=true, l'assistant est ainsi disponible sans passer par Paramètres.
     */
    public function up(): void
    {
        DB::table('organizations')->update(['certy_ia_enabled' => true]);
    }

    public function down(): void
    {
        DB::table('organizations')->update(['certy_ia_enabled' => false]);
    }
};
