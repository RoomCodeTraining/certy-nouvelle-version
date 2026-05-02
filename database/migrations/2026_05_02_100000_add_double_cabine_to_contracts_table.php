<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('is_double_cabine')->default(false)->after('endorsement_type');
            $table->foreignId('second_vehicle_id')->nullable()->after('is_double_cabine')->constrained('vehicles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['second_vehicle_id']);
            $table->dropColumn(['is_double_cabine', 'second_vehicle_id']);
        });
    }
};
