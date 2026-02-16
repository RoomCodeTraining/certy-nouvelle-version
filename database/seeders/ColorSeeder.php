<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['code' => 'BLK', 'name' => 'Noir'],
            ['code' => 'WHT', 'name' => 'Blanc'],
            ['code' => 'RED', 'name' => 'Rouge'],
            ['code' => 'GRN', 'name' => 'Vert'],
            ['code' => 'BLU', 'name' => 'Bleu'],
            ['code' => 'YEL', 'name' => 'Jaune'],
            ['code' => 'GRY', 'name' => 'Gris'],
            ['code' => 'SLV', 'name' => 'Argent'],
            ['code' => 'GLD', 'name' => 'Or'],
            ['code' => 'BRN', 'name' => 'Marron'],
            ['code' => 'ORG', 'name' => 'Orange'],
            ['code' => 'PRP', 'name' => 'Violet'],
            ['code' => 'PNK', 'name' => 'Rose'],
            ['code' => 'BGE', 'name' => 'Beige'],
            ['code' => 'CRM', 'name' => 'Crème'],
        ];

        foreach ($colors as $data) {
            Color::updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'code' => $data['code']]
            );
        }
    }
}
