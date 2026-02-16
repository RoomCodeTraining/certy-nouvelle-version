<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpc_pricing_grids', function (Blueprint $table) {
            $table->id();
            $table->string('duration', 20)->index();
            $table->string('payload_range', 30)->index();
            $table->unsignedInteger('base_amount');
            $table->unsignedInteger('rc_amount');
            $table->unsignedInteger('defence_appeal_amount');
            $table->unsignedInteger('person_transport_amount');
            $table->unsignedInteger('accessory_amount');
            $table->unsignedInteger('taxes_amount');
            $table->unsignedInteger('cedeao_amount');
            $table->unsignedInteger('fga_amount');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['duration', 'payload_range']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpc_pricing_grids');
    }
};
