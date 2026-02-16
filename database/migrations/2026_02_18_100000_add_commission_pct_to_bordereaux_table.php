<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bordereaux', function (Blueprint $table) {
            $table->decimal('commission_pct', 5, 2)->nullable()->after('total_commission')->comment('Commission courtier en % (config compagnie au moment de la création)');
        });
    }

    public function down(): void
    {
        Schema::table('bordereaux', function (Blueprint $table) {
            $table->dropColumn('commission_pct');
        });
    }
};
