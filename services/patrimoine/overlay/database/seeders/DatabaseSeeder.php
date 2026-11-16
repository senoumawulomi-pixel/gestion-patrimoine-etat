<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Categorie;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [];
        foreach (['Immobilier', 'Véhicules', 'Mobilier', 'Matériel informatique', 'Équipements'] as $nom) {
            $cats[$nom] = Categorie::firstOrCreate(['nom' => $nom])->id;
        }
        if (Bien::count() > 0) {
            return;
        }
        $demo = [
            ['PAT-2020-0001', 'Bâtiment administratif A', 'Immobilier', 'Lomé', 'Ministère des Finances', 'bon', '2015-03-01', 450000000, 40],
            ['PAT-2021-0002', 'Pick-up Toyota Hilux', 'Véhicules', 'Lomé', 'Ministère de la Santé', 'usage', '2019-06-15', 28000000, 8],
            ['PAT-2022-0003', 'Ordinateurs de bureau (lot 20)', 'Matériel informatique', 'Kara', 'Direction du Budget', 'bon', '2022-01-10', 12000000, 4],
            ['PAT-2018-0004', 'Photocopieur industriel', 'Équipements', 'Lomé', 'Archives nationales', 'hors_service', '2016-09-20', 3500000, 5],
            ['PAT-2023-0005', 'Mobilier de salle de réunion', 'Mobilier', 'Lomé', 'Primature', 'neuf', '2023-04-02', 6000000, 10],
        ];
        foreach ($demo as [$code, $des, $cat, $loc, $srv, $etat, $date, $val, $duree]) {
            Bien::create([
                'code' => $code, 'designation' => $des, 'categorie_id' => $cats[$cat], 'localisation' => $loc,
                'service_affecte' => $srv, 'etat' => $etat, 'statut' => 'actif',
                'date_acquisition' => $date, 'valeur_acquisition' => $val, 'duree_amortissement' => $duree,
            ]);
        }
    }
}
