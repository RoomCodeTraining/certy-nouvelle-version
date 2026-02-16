<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('body_type')->nullable()->after('vehicle_model_id');
            $table->decimal('payload_capacity', 10, 2)->nullable()->after('color_id');
            $table->unsignedInteger('engine_capacity')->nullable()->after('energy_source_id');
            $table->unsignedSmallInteger('seat_count')->nullable()->after('engine_capacity');
            $table->date('first_registration_date')->nullable()->after('year_of_first_registration');
            $table->string('registration_card_number')->nullable()->after('first_registration_date');
            $table->string('chassis_number')->nullable()->after('registration_card_number');
            $table->decimal('new_value', 12, 2)->nullable()->after('chassis_number');
            $table->decimal('replacement_value', 12, 2)->nullable()->after('new_value');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'body_type',
                'payload_capacity',
                'engine_capacity',
                'seat_count',
                'first_registration_date',
                'registration_card_number',
                'chassis_number',
                'new_value',
                'replacement_value',
            ]);
        });
    }
};
