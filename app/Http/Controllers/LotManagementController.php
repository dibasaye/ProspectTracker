<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotManagementController extends Controller
{
    /**
     * Affiche le formulaire d'ajout en lot de plusieurs lots
     */
    public function bulkCreate(Site $site)
    {
        return view('lots.bulk-create', compact('site'));
    }

    /**
     * Enregistre plusieurs lots à la fois
     */
    public function bulkStore(Request $request, Site $site)
    {
        $validated = $request->validate([
            'lot_numbers' => 'required|string',
            'default_area' => 'nullable|numeric|min:0',
            'auto_calculate_prices' => 'boolean',
        ]);

        // Traiter les numéros de lots (séparés par virgules, nouvelles lignes, etc.)
        $lotNumbers = $this->parseLotNumbers($validated['lot_numbers']);
        
        if (empty($lotNumbers)) {
            return back()->withErrors(['lot_numbers' => 'Aucun numéro de lot valide trouvé.']);
        }

        $created = 0;
        $errors = [];

        DB::transaction(function () use ($site, $lotNumbers, $validated, &$created, &$errors) {
            foreach ($lotNumbers as $lotNumber) {
                // Vérifier si le lot existe déjà
                if ($site->lots()->where('lot_number', $lotNumber)->exists()) {
                    $errors[] = "Le lot {$lotNumber} existe déjà.";
                    continue;
                }

                $lot = new Lot([
                    'site_id' => $site->id,
                    'lot_number' => $lotNumber,
                    'area' => $validated['default_area'] ?? 150, // Superficie par défaut
                    'position' => 'interieur', // Position par défaut (peut être modifiée après)
                    'status' => 'disponible',
                    'base_price' => $site->price_interieur ?? 0,
                    'final_price' => $site->price_interieur ?? 0,
                ]);

                // Calculer automatiquement les prix selon les plans de paiement
                if ($validated['auto_calculate_prices'] ?? true) {
                    $lot->calculatePrices();
                }

                $lot->save();
                $created++;
            }
        });

        $message = "{$created} lot(s) créé(s) avec succès.";
        if (!empty($errors)) {
            $message .= " Erreurs : " . implode(', ', $errors);
        }

        return redirect()->route('sites.lots', $site)->with('success', $message);
    }

    /**
     * Met à jour en lot la position et les prix de plusieurs lots
     */
    public function bulkUpdatePosition(Request $request, Site $site)
    {
        $validated = $request->validate([
            'lot_ids' => 'required|array',
            'lot_ids.*' => 'exists:lots,id',
            'position' => 'required|in:angle,facade,interieur',
            'custom_area' => 'nullable|numeric|min:0',
            'recalculate_prices' => 'boolean',
        ]);

        $updated = 0;

        DB::transaction(function () use ($validated, $site, &$updated) {
            $lots = Lot::whereIn('id', $validated['lot_ids'])
                      ->where('site_id', $site->id)
                      ->get();

            foreach ($lots as $lot) {
                $lot->position = $validated['position'];
                
                if (!empty($validated['custom_area'])) {
                    $lot->area = $validated['custom_area'];
                }

                // Recalculer les prix si demandé
                if ($validated['recalculate_prices'] ?? true) {
                    $lot->is_manually_priced = false;
                    $lot->calculatePrices();
                } else {
                    $lot->is_manually_priced = true;
                }

                $lot->save();
                $updated++;
            }
        });

        return back()->with('success', "{$updated} lot(s) mis à jour avec succès.");
    }

    /**
     * Parse les numéros de lots depuis une chaîne de caractères
     */
    private function parseLotNumbers(string $input): array
    {
        // Séparer par virgules, nouvelles lignes, espaces, etc.
        $numbers = preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY);
        
        // Nettoyer et valider chaque numéro
        $validNumbers = [];
        foreach ($numbers as $number) {
            $cleaned = trim($number);
            if (!empty($cleaned) && strlen($cleaned) <= 20) {
                $validNumbers[] = $cleaned;
            }
        }

        return array_unique($validNumbers);
    }
}
