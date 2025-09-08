<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Lot;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        // Create Parcelles Assainies Site
        $site1 = Site::firstOrCreate(
            ['name' => 'Résidence Téranga'],
            [
                'location' => 'Parcelles Assainies, Dakar',
                'description' => 'Un projet résidentiel moderne avec toutes les commodités dans un quartier prisé de Dakar.',
                'total_area' => 5000.00,
                'total_lots' => 50,
                'angle_price' => 10000000,  // 10,000,000 FCFA
                'facade_price' => 9000000,   // 9,000,000 FCFA
                'interior_price' => 8000000, // 8,000,000 FCFA
                'one_year_price' => 1000000,  // 1,000,000 FCFA
                'two_years_price' => 500000,  // 500,000 FCFA
                'three_years_price' => 0,     // 0 FCFA (non activé)
                'reservation_fee' => 500000,  // 500,000 FCFA
                'membership_fee' => 200000,   // 200,000 FCFA
                'payment_plan' => '24_months',
                'amenities' => ['electricite', 'eau_courante', 'routes_pavees', 'espaces_verts', 'securite'],
                'status' => 'active',
                'latitude' => 14.7645,
                'longitude' => -17.3660,
                'is_active' => true,
            ]
        );

        // Create lots for Résidence Téranga
        $positions = ['angle', 'facade', 'interieur'];
        $positionSupplements = ['angle' => 15000, 'facade' => 8000, 'interieur' => 0];
        
        for ($i = 1; $i <= 50; $i++) {
            $lotNumber = sprintf('T%03d', $i);
            
            // Vérifier si le lot existe déjà
            $existingLot = Lot::where('site_id', $site1->id)
                ->where('lot_number', $lotNumber)
                ->first();
            
            if (!$existingLot) {
                $position = $positions[array_rand($positions)];
                $area = rand(200, 500);
                // Calcul du prix de base en fonction de la position
                $basePrice = match($position) {
                    'angle' => $site1->angle_price,
                    'facade' => $site1->facade_price,
                    'interieur' => $site1->interior_price,
                    default => $site1->interior_price
                };
                $positionSupplement = $positionSupplements[$position] * $area;
                $finalPrice = $basePrice + $positionSupplement;

                Lot::create([
                    'site_id' => $site1->id,
                    'lot_number' => $lotNumber,
                    'area' => $area,
                    'position' => $position,
                    'status' => 'disponible',
                    'base_price' => $basePrice,
                    'position_supplement' => $positionSupplement,
                    'final_price' => $finalPrice,
                    'has_utilities' => true,
                    'features' => $position === 'angle' ? ['double_facade', 'acces_facile'] : ['acces_normal'],
                ]);
            }
        }

        // Create Keur Massar Site
        $site2 = Site::firstOrCreate(
            ['name' => 'Domaine Salam'],
            [
                'location' => 'Keur Massar, Dakar',
                'description' => 'Un développement résidentiel abordable avec des infrastructures modernes.',
                'total_area' => 8000.00,
                'total_lots' => 80,
                'angle_price' => 8000000,    // 8,000,000 FCFA
                'facade_price' => 7000000,   // 7,000,000 FCFA
                'interior_price' => 6000000, // 6,000,000 FCFA
                'one_year_price' => 800000,  // 800,000 FCFA
                'two_years_price' => 400000, // 400,000 FCFA
                'three_years_price' => 200000, // 200,000 FCFA
                'reservation_fee' => 300000, // 300,000 FCFA
                'membership_fee' => 150000,  // 150,000 FCFA
                'payment_plan' => '36_months',
                'amenities' => ['electricite', 'eau_courante', 'routes_goudronnees', 'mosquee', 'ecole'],
                'status' => 'active',
                'latitude' => 14.7833,
                'longitude' => -17.3167,
                'is_active' => true,
            ]
        );

        // Create lots for Domaine Salam
        for ($i = 1; $i <= 80; $i++) {
            $lotNumber = sprintf('S%03d', $i);
            
            // Vérifier si le lot existe déjà
            $existingLot = Lot::where('site_id', $site2->id)
                ->where('lot_number', $lotNumber)
                ->first();
            
            if (!$existingLot) {
                $position = $positions[array_rand($positions)];
                $area = rand(250, 400);
                // Calcul du prix de base en fonction de la position
                $basePrice = match($position) {
                    'angle' => $site2->angle_price,
                    'facade' => $site2->facade_price,
                    'interieur' => $site2->interior_price,
                    default => $site2->interior_price
                };
                $positionSupplement = $positionSupplements[$position] * $area;
                $finalPrice = $basePrice + $positionSupplement;

                Lot::create([
                    'site_id' => $site2->id,
                    'lot_number' => $lotNumber,
                    'area' => $area,
                    'position' => $position,
                    'status' => 'disponible',
                    'base_price' => $basePrice,
                    'position_supplement' => $positionSupplement,
                    'final_price' => $finalPrice,
                    'has_utilities' => true,
                    'features' => ['acces_normal'],
                ]);
            }
        }
    }
}