<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optional_guarantees', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->decimal('rate', 8, 3);
            $table->string('base'); // 'new' ou 'venale'
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Valeurs par défaut
        DB::table('optional_guarantees')->insert([
            [
                'code' => 'DOMMAGE_VEHICULE',
                'label' => 'Dommage au véhicule',
                'rate' => 6.5,
                'base' => 'new',
                'enabled' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DOMMAGE_TOUT_ACCIDENT',
                'label' => 'Dommage tout accident',
                'rate' => 7.5,
                'base' => 'new',
                'enabled' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BRIS_GLACE',
                'label' => 'Bris de glace',
                'rate' => 0.2,
                'base' => 'new',
                'enabled' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'INCENDIE',
                'label' => 'Incendie',
                'rate' => 1.5,
                'base' => 'venale',
                'enabled' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'VOL_AGRESSION',
                'label' => 'Vol / Agression',
                'rate' => 1.5,
                'base' => 'venale',
                'enabled' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('optional_guarantees');
    }
};

