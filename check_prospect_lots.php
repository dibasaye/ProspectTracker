<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Prospect;

// Récupérer le prospect avec l'ID 1
$prospect = Prospect::with(['lots'])->find(1);

if (!$prospect) {
    die("Aucun prospect trouvé avec l'ID 1\n");
}

echo "Prospect: {$prospect->first_name} {$prospect->last_name} (ID: {$prospect->id})\n";
echo "Nombre de lots associés: " . $prospect->lots->count() . "\n\n";

// Afficher les lots associés
foreach ($prospect->lots as $lot) {
    echo "- Lot #{$lot->id} - Numéro: {$lot->lot_number} - Statut: {$lot->status}\n";
}

// Vérifier directement dans la base de données
$lots = DB::table('lots')->where('client_id', $prospect->id)->get();
echo "\nLots trouvés directement dans la base de données: " . $lots->count() . "\n";

foreach ($lots as $lot) {
    echo "- Lot #{$lot->id} - Numéro: {$lot->lot_number} - Statut: {$lot->status}\n";
}

// Vérifier la relation inverse
echo "\nVérification de la relation inverse (Lot -> Prospect):\n";
$lot = DB::table('lots')->where('client_id', $prospect->id)->first();
if ($lot) {
    $client = DB::table('prospects')->find($lot->client_id);
    if ($client) {
        echo "Le lot #{$lot->id} est associé au prospect: {$client->first_name} {$client->last_name} (ID: {$client->id})\n";
    } else {
        echo "ERREUR: Aucun client trouvé pour le lot #{$lot->id}\n";
    }
} else {
    echo "Aucun lot trouvé pour ce prospect.\n";
}
