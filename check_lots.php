<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Vérifier les lots avec client_id non nul
$lots = DB::table('lots')->whereNotNull('client_id')->get();

echo "Lots avec client_id non nul : " . $lots->count() . "\n\n";

foreach ($lots as $lot) {
    echo "Lot #{$lot->id} - Client ID: {$lot->client_id}\n";
    
    // Vérifier si le client existe
    $client = DB::table('prospects')->find($lot->client_id);
    if ($client) {
        echo "  Client trouvé: {$client->first_name} {$client->last_name} (ID: {$client->id})\n";
    } else {
        echo "  ERREUR: Aucun client trouvé avec l'ID {$lot->client_id}\n";
    }
    
    echo "\n";
}

// Vérifier la structure de la table lots
echo "\nStructure de la table lots :\n";
$columns = DB::select('SHOW COLUMNS FROM lots');
foreach ($columns as $column) {
    echo "- {$column->Field} : {$column->Type} " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}
