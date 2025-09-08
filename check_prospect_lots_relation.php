<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Prospect;
use App\Models\Lot;

// Récupérer le prospect avec l'ID 1
$prospect = Prospect::find(1);

if (!$prospect) {
    die("Aucun prospect trouvé avec l'ID 1\n");
}

echo "Prospect: {$prospect->first_name} {$prospect->last_name} (ID: {$prospect->id})\n";

// Méthode 1: Via la relation Eloquent
echo "\nMéthode 1: Via la relation Eloquent\n";
$lots = $prospect->lots()->with('site')->get();
echo "Nombre de lots via relation: " . $lots->count() . "\n";
foreach ($lots as $lot) {
    echo "- Lot #{$lot->id} - Numéro: {$lot->lot_number} - Statut: {$lot->status}";
    echo " - Site: " . ($lot->site ? $lot->site->name : 'Aucun site') . "\n";
}

// Méthode 2: Requête directe
echo "\nMéthode 2: Requête directe\n";
$directLots = DB::table('lots')
    ->where('client_id', $prospect->id)
    ->leftJoin('sites', 'lots.site_id', '=', 'sites.id')
    ->select('lots.*', 'sites.name as site_name')
    ->get();

echo "Nombre de lots via requête directe: " . $directLots->count() . "\n";
foreach ($directLots as $lot) {
    echo "- Lot #{$lot->id} - Numéro: {$lot->lot_number} - Statut: {$lot->status}";
    echo " - Site: " . ($lot->site_name ?? 'Aucun site') . "\n";
}

// Vérifier la vue
$view = view('prospects.show', ['prospect' => $prospect->load('lots.site')]);
$viewContents = $view->render();

// Vérifier si le message "Ce client n'a encore réservé aucun lot" est présent
if (strpos($viewContents, 'Ce client n\'a encore réservé aucun lot') !== false) {
    echo "\nLe message 'Ce client n'a encore réservé aucun lot' est présent dans la vue.\n";
    
    // Vérifier si les données des lots sont présentes dans la vue
    if (strpos($viewContents, 'Lots réservés') !== false) {
        echo "La section 'Lots réservés' est présente dans la vue.\n";
    }
    
    // Vérifier la condition qui affiche le message
    $lotsCount = $prospect->lots->count();
    echo "Nombre de lots dans la relation: $lotsCount\n";
    
    if ($lotsCount === 0) {
        echo "ERREUR: La relation lots est vide malgré les données en base.\n";
    }
} else {
    echo "\nLe message n'est pas présent dans la vue.\n";
}
