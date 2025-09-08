<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Vérifier les réservations pour les lots 51, 52 et 53
$reservations = DB::table('reservations')
    ->whereIn('lot_id', [51, 52, 53])
    ->get();

echo "Réservations trouvées : " . $reservations->count() . "\n\n";

foreach ($reservations as $reservation) {
    echo "Réservation #{$reservation->id} - Lot ID: {$reservation->lot_id} - Prospect ID: {$reservation->prospect_id}\n";
    
    // Vérifier le lot
    $lot = DB::table('lots')->find($reservation->lot_id);
    if ($lot) {
        echo "  Lot #{$lot->id} - Statut: {$lot->status} - Client ID: " . ($lot->client_id ?? 'NULL') . "\n";
    } else {
        echo "  ERREUR: Lot non trouvé\n";
    }
    
    // Vérifier le prospect
    $prospect = DB::table('prospects')->find($reservation->prospect_id);
    if ($prospect) {
        echo "  Prospect: {$prospect->first_name} {$prospect->last_name} (ID: {$prospect->id})\n";
    } else {
        echo "  ERREUR: Prospect non trouvé\n";
    }
    
    echo "\n";
}

// Vérifier la structure de la table reservations
echo "\nStructure de la table reservations :\n";
$columns = DB::select('SHOW COLUMNS FROM reservations');
foreach ($columns as $column) {
    echo "- {$column->Field} : {$column->Type} " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}
