<?php

namespace Database\Seeders;

use App\Models\CirculationZone;
use Illuminate\Database\Seeder;

class CirculationZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            ['code' => 'CIV001', 'description' => 'Abengourou'],
            ['code' => 'CIV002', 'description' => 'Abidjan'],
            ['code' => 'CIV003', 'description' => 'Aboisso'],
            ['code' => 'CIV004', 'description' => 'Abongoua'],
            ['code' => 'CIV005', 'description' => 'Adaou'],
            ['code' => 'CIV006', 'description' => 'Adiaké'],
            ['code' => 'CIV007', 'description' => 'Adjouan'],
            ['code' => 'CIV008', 'description' => 'Adzopé'],
            ['code' => 'CIV009', 'description' => 'Adzopé (Ananguié)'],
            ['code' => 'CIV010', 'description' => 'Affery (Afféry)'],
            ['code' => 'CIV011', 'description' => 'Agbaou'],
            ['code' => 'CIV012', 'description' => 'Agboville'],
            ['code' => 'CIV013', 'description' => 'Agnibilékrou'],
            ['code' => 'CIV014', 'description' => 'Ahouanou'],
            ['code' => 'CIV015', 'description' => 'Ahoutoué'],
            ['code' => 'CIV016', 'description' => 'Akouédo'],
            ['code' => 'CIV017', 'description' => 'Akoupé'],
            ['code' => 'CIV018', 'description' => 'Alépé'],
            ['code' => 'CIV019', 'description' => 'Alounamouénou'],
            ['code' => 'CIV020', 'description' => 'Annépé'],
            ['code' => 'CIV021', 'description' => 'Anoumaba'],
            ['code' => 'CIV022', 'description' => 'Anyama'],
            ['code' => 'CIV023', 'description' => 'Arrah'],
            ['code' => 'CIV024', 'description' => 'Assaoufoué'],
            ['code' => 'CIV025', 'description' => 'Attiégouakro'],
            ['code' => 'CIV026', 'description' => 'Attoutou A'],
            ['code' => 'CIV027', 'description' => 'Ayamé'],
            ['code' => 'CIV028', 'description' => 'Azaguié'],
            ['code' => 'CIV029', 'description' => 'Bacanda'],
            ['code' => 'CIV030', 'description' => 'Badikaha'],
            ['code' => 'CIV031', 'description' => 'Bako'],
            ['code' => 'CIV032', 'description' => 'Baléko'],
            ['code' => 'CIV033', 'description' => 'Bambalouma'],
            ['code' => 'CIV034', 'description' => 'Bandakagni-Sokoura'],
            ['code' => 'CIV035', 'description' => 'Bangolo'],
            ['code' => 'CIV036', 'description' => 'Bangoua'],
            ['code' => 'CIV037', 'description' => 'Banneu'],
            ['code' => 'CIV038', 'description' => 'Bassadjin'],
            ['code' => 'CIV039', 'description' => 'Batéguédia II'],
            ['code' => 'CIV040', 'description' => 'Bayota'],
            ['code' => 'CIV041', 'description' => 'Bazra-Nattis'],
            ['code' => 'CIV042', 'description' => 'Bécouéfin'],
            ['code' => 'CIV043', 'description' => 'Béoumi'],
            ['code' => 'CIV044', 'description' => 'Bettié'],
            ['code' => 'CIV045', 'description' => 'Biankouma'],
            ['code' => 'CIV046', 'description' => 'Biankouma (Santa)'],
            ['code' => 'CIV047', 'description' => 'Biéby'],
            ['code' => 'CIV048', 'description' => 'Bin-Houyé'],
            ['code' => 'CIV049', 'description' => 'Bingerville'],
            ['code' => 'CIV050', 'description' => 'Blapleu'],
            ['code' => 'CIV051', 'description' => 'Bléniméouin'],
            ['code' => 'CIV052', 'description' => 'Blességué'],
            ['code' => 'CIV053', 'description' => 'Bloléquin'],
            ['code' => 'CIV054', 'description' => 'Boahia'],
            ['code' => 'CIV055', 'description' => 'Bocanda'],
            ['code' => 'CIV056', 'description' => 'Bogouiné'],
            ['code' => 'CIV057', 'description' => 'Boli'],
            ['code' => 'CIV058', 'description' => 'Bondo'],
            ['code' => 'CIV059', 'description' => 'Bondoukou'],
            ['code' => 'CIV060', 'description' => 'Bongo'],
            ['code' => 'CIV061', 'description' => 'Bongouanou'],
            ['code' => 'CIV062', 'description' => 'Bonon'],
            ['code' => 'CIV063', 'description' => 'Bonoua'],
            ['code' => 'CIV064', 'description' => 'Boromba'],
            ['code' => 'CIV065', 'description' => 'Botro'],
            ['code' => 'CIV066', 'description' => 'Bouaflé'],
            ['code' => 'CIV067', 'description' => 'Bouaké'],
            ['code' => 'CIV068', 'description' => 'Bouandougou'],
            ['code' => 'CIV069', 'description' => 'Boudépé'],
            ['code' => 'CIV070', 'description' => 'Bougousso'],
            ['code' => 'CIV071', 'description' => 'Bouna'],
            ['code' => 'CIV072', 'description' => 'Boundiali (Diogo)'],
            ['code' => 'CIV073', 'description' => 'Boundiali (Toumo)'],
            ['code' => 'CIV074', 'description' => 'Boundiali (Ville)'],
            ['code' => 'CIV075', 'description' => 'Boundiali (Yama)'],
            ['code' => 'CIV076', 'description' => 'Brobo (Gorobo)'],
            ['code' => 'CIV077', 'description' => 'Brofodoumé'],
            ['code' => 'CIV078', 'description' => 'Buyo'],
            ['code' => 'CIV079', 'description' => 'Céchi'],
            ['code' => 'CIV080', 'description' => 'Dabakala'],
            ['code' => 'CIV081', 'description' => 'Dabéko'],
            ['code' => 'CIV082', 'description' => 'Dabou'],
            ['code' => 'CIV083', 'description' => 'Dabouyo'],
            ['code' => 'CIV084', 'description' => 'Dah-Zagna'],
            ['code' => 'CIV085', 'description' => 'Dakpadou'],
            ['code' => 'CIV086', 'description' => 'Daleu'],
            ['code' => 'CIV087', 'description' => 'Daloa'],
            ['code' => 'CIV088', 'description' => 'Danané'],
            ['code' => 'CIV089', 'description' => 'Danguira'],
            ['code' => 'CIV090', 'description' => 'Daoukro'],
            ['code' => 'CIV091', 'description' => 'Daoukro (Ananda)'],
            ['code' => 'CIV092', 'description' => 'Diabo'],
            ['code' => 'CIV093', 'description' => 'Diamarakro'],
            ['code' => 'CIV094', 'description' => 'Diawala'],
            ['code' => 'CIV095', 'description' => 'Diboké'],
            ['code' => 'CIV096', 'description' => 'Didiévi'],
            ['code' => 'CIV097', 'description' => 'Diégonefla'],
            ['code' => 'CIV098', 'description' => 'Diéouzon'],
            ['code' => 'CIV099', 'description' => 'Digbeugnoa'],
            ['code' => 'CIV100', 'description' => 'Dignago'],
        ];

        // Ajout automatique des codes manquants jusqu'à CIV400
        for ($i = 101; $i <= 400; $i++) {
            $code = 'CIV'.str_pad($i, 3, '0', STR_PAD_LEFT);
            if (! collect($zones)->contains('code', $code)) {
                $zones[] = ['code' => $code, 'description' => ''];
            }
        }

        foreach ($zones as $zone) {
            $name = $zone['description'] !== '' ? $zone['description'] : $zone['code'];
            CirculationZone::updateOrCreate(
                ['code' => $zone['code']],
                ['name' => $name, 'code' => $zone['code']]
            );
        }
    }
}
