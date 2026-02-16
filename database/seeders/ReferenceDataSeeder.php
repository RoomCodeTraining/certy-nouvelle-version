<?php

namespace Database\Seeders;

use App\Models\CirculationZone;
use App\Models\Color;
use App\Models\EnergySource;
use App\Models\Profession;
use App\Models\VehicleCategory;
use App\Models\VehicleGender;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
    }

    private function seedCirculationZones(): void
    {
        $zones = [
            ['name' => 'Abidjan', 'code' => 'ABJ'],
            ['name' => 'Intérieur', 'code' => 'INT'],
        ];
        foreach ($zones as $z) {
            CirculationZone::firstOrCreate(['code' => $z['code']], $z);
        }
    }

    private function seedEnergySources(): void
    {
        $sources = [
            ['name' => 'Essence', 'code' => 'ESS'],
            ['name' => 'Diesel', 'code' => 'DIE'],
            ['name' => 'GPL', 'code' => 'GPL'],
            ['name' => 'Électrique', 'code' => 'ELEC'],
        ];
        foreach ($sources as $s) {
            EnergySource::firstOrCreate(['code' => $s['code']], $s);
        }
    }

    private function seedVehicleUsages(): void
    {
        $usages = [
            ['name' => 'Particulier', 'code' => 'PART'],
            ['name' => 'Commercial', 'code' => 'COMM'],
        ];
        foreach ($usages as $u) {
            VehicleUsage::firstOrCreate(['code' => $u['code']], $u);
        }
    }

    private function seedVehicleTypes(): void
    {
        $types = [
            ['name' => 'Voiture particulière', 'code' => 'VP'],
            ['name' => 'Deux roues', 'code' => '2R'],
        ];
        foreach ($types as $t) {
            VehicleType::firstOrCreate(['code' => $t['code']], $t);
        }
    }

    private function seedVehicleCategories(): void
    {
        $categories = [
            ['name' => 'Catégorie A', 'code' => 'A'],
            ['name' => 'Catégorie B', 'code' => 'B'],
        ];
        foreach ($categories as $c) {
            VehicleCategory::firstOrCreate(['code' => $c['code']], $c);
        }
    }

    private function seedVehicleGenders(): void
    {
        $genders = [
            ['name' => 'Masculin', 'code' => 'M'],
            ['name' => 'Féminin', 'code' => 'F'],
        ];
        foreach ($genders as $g) {
            VehicleGender::firstOrCreate(['code' => $g['code']], $g);
        }
    }

    private function seedColors(): void
    {
        $colors = [
            ['name' => 'Noir', 'code' => 'NOIR'],
            ['name' => 'Blanc', 'code' => 'BLANC'],
            ['name' => 'Gris', 'code' => 'GRIS'],
            ['name' => 'Bleu', 'code' => 'BLEU'],
            ['name' => 'Rouge', 'code' => 'ROUGE'],
        ];
        foreach ($colors as $c) {
            Color::firstOrCreate(['code' => $c['code']], $c);
        }
    }

    private function seedProfessions(): void
    {
        $professions = ['Salarié', 'Commerçant', 'Fonctionnaire', 'Libéral', 'Retraité', 'Sans emploi', 'Autre'];
        foreach ($professions as $name) {
            Profession::firstOrCreate(['name' => $name]);
        }
    }
}
