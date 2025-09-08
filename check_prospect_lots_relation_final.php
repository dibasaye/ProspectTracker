<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Prospect;
use App\Models\Lot;

// 1. Vérifier le prospect avec ID 1
$prospect = Prospect::find(1);

if (!$prospect) {
    die("Aucun prospect trouvé avec l'ID 1\n");
}

echo "=== DÉBOGAGE RELATION PROSPECT-LOTS ===\n";
echo "Prospect ID: " . $prospect->id . "\n";
echo "Nom: " . ($prospect->full_name ?? 'Non défini') . "\n\n";

// 2. Vérifier la relation lots
echo "2. Vérification de la relation lots()\n";

// Méthode 1: Via la relation Eloquent
$lots = $prospect->lots()->with('site')->get();
echo "- Nombre de lots via relation: " . $lots->count() . "\n";

if ($lots->isNotEmpty()) {
    foreach ($lots as $lot) {
        echo "  - Lot #" . $lot->id . ": " . $lot->lot_number . " (Site: " . ($lot->site ? $lot->site->name : 'Aucun') . ")\n";
        echo "    Status: " . $lot->status . "\n";
        echo "    Status Color: " . ($lot->status_color ?? 'Non défini') . "\n";
        echo "    Status Label: " . ($lot->status_label ?? 'Non défini') . "\n";
    }
} else {
    echo "  - Aucun lot trouvé via la relation.\n";
}

// Méthode 2: Requête directe
echo "\n3. Vérification directe en base de données\n";
$directLots = DB::table('lots')
    ->where('client_id', $prospect->id)
    ->leftJoin('sites', 'lots.site_id', '=', 'sites.id')
    ->select('lots.*', 'sites.name as site_name')
    ->get();

echo "- Nombre de lots en base de données: " . $directLots->count() . "\n";

if ($directLots->isNotEmpty()) {
    foreach ($directLots as $lot) {
        echo "  - Lot #" . $lot->id . ": " . $lot->lot_number . " (Site: " . ($lot->site_name ?? 'Aucun') . ")\n";
        echo "    Status: " . ($lot->status ?? 'Non défini') . "\n";
        
        // Créer une instance de Lot pour accéder aux accesseurs
        $lotModel = Lot::find($lot->id);
        if ($lotModel) {
            echo "    Status Color: " . $lotModel->status_color . "\n";
            echo "    Status Label: " . $lotModel->status_label . "\n";
        }
    }
} else {
    echo "  - Aucun lot trouvé en base de données.\n";}

// 4. Vérifier la structure de la table lots
echo "\n4. Structure de la table lots\n";
$columns = DB::select('SHOW COLUMNS FROM lots');
foreach ($columns as $column) {
    echo "- " . $column->Field . " : " . $column->Type . " " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";}

// 5. Vérifier les clés étrangères
echo "\n5. Vérification des clés étrangères\n";
$foreignKeys = DB::select("
    SELECT 
        TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
    FROM
n        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
        REFERENCED_TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'lots'
        AND COLUMN_NAME = 'client_id'
");

if (count($foreignKeys) > 0) {
    foreach ($foreignKeys as $fk) {
        echo "- Clé étrangère trouvée: " . $fk->CONSTRAINT_NAME . "\n";
        echo "  Table: " . $fk->TABLE_NAME . " (" . $fk->COLUMN_NAME . ")\n";
        echo "  Référence: " . $fk->REFERENCED_TABLE_NAME . " (" . $fk->REFERENCED_COLUMN_NAME . ")\n";
    }
} else {
    echo "- Aucune clé étrangère trouvée pour client_id dans la table lots.\n";
}

echo "\n=== FIN DU DÉBOGAGE ===\n";
