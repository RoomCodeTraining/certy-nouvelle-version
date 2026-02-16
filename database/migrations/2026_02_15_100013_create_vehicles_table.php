<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();
            $table->string('registration_number')->nullable();
            $table->foreignId('circulation_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('energy_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_usage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_gender_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('fiscal_power')->nullable();
            $table->integer('year_of_first_registration')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
