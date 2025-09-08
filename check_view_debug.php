<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Prospect;

// Récupérer le prospect avec les relations chargées
$prospect = Prospect::with(['lots.site'])->find(1);

if (!$prospect) {
    die("Aucun prospect trouvé avec l'ID 1\n");
}

// Afficher les informations de débogage
echo "=== DÉBOGAGE VUE PROSPECT ===\n";

echo "\n1. Informations du prospect:";
echo "\n- ID: " . $prospect->id;
echo "\n- Nom complet: " . $prospect->full_name;
echo "\n- Nombre de lots chargés: " . $prospect->lots->count();

// Vérifier la collection de lots
echo "\n\n2. Vérification de la collection de lots:";
if ($prospect->lots->isEmpty()) {
    echo "\n- La collection de lots est VIDE.";
} else {
    echo "\n- La collection contient " . $prospect->lots->count() . " lots.";
    foreach ($prospect->lots as $lot) {
        echo "\n  - Lot #" . $lot->id . ": " . $lot->lot_number . " (Site: " . ($lot->site ? $lot->site->name : 'Aucun') . ")";
    }
}

// Vérifier la relation
echo "\n\n3. Vérification de la relation lots():";
$relation = $prospect->lots();
echo "\n- Type de relation: " . get_class($relation);
$directQuery = $relation->toSql();
echo "\n- Requête SQL: " . $directQuery;

// Exécuter la requête directement
echo "\n\n4. Exécution de la requête directe:";
$bindings = $relation->getBindings();
$results = \DB::select($directQuery, $bindings);
echo "\n- Nombre de résultats: " . count($results);

// Vérifier les modèles
echo "\n\n5. Vérification des modèles:";
$prospect->load('lots'); // Recharger la relation
echo "\n- Après rechargement, nombre de lots: " . $prospect->lots->count();

// Vérifier si la collection est une instance de Collection
if ($prospect->lots instanceof \Illuminate\Database\Eloquent\Collection) {
    echo "\n- La relation retourne bien une Collection Eloquent.";
} else {
    echo "\n- ATTENTION: La relation ne retourne PAS une Collection Eloquent.";
    echo "\n  Type de l'objet: " . get_class($prospect->lots);
}

// Vérifier si les attributs sont accessibles
echo "\n\n6. Vérification des attributs du premier lot:";
if ($prospect->lots->isNotEmpty()) {
    $firstLot = $prospect->lots->first();
    echo "\n- Premier lot:";
    echo "\n  - ID: " . $firstLot->id;
    echo "\n  - Numéro: " . $firstLot->lot_number;
    echo "\n  - Statut: " . $firstLot->status;
    echo "\n  - Site: " . ($firstLot->site ? $firstLot->site->name : 'Aucun');
} else {
    echo "\n- Aucun lot trouvé dans la collection.";
}

// Vérifier la vue sans passer par le rendu complet
// pour éviter les problèmes d'authentification
echo "\n\n7. Vérification du contenu de la vue (sans rendu complet):";

// Vérifier la logique de la vue
$shouldShowNoLotsMessage = $prospect->lots->isEmpty();
$shouldShowLotsList = !$prospect->lots->isEmpty();

echo "\n- La vue devrait afficher le message 'Aucun lot': " . ($shouldShowNoLotsMessage ? 'OUI' : 'NON');
echo "\n- La vue devrait afficher la liste des lots: " . ($shouldShowLotsList ? 'OUI' : 'NON');

// Vérifier les données qui seraient affichées
if ($shouldShowLotsList) {
    echo "\n\nDétails des lots qui devraient s'afficher:";
    foreach ($prospect->lots as $lot) {
        echo "\n- Lot #" . $lot->id . ":";
        echo "\n  - Numéro: " . $lot->lot_number;
        echo "\n  - Statut: " . $lot->status;
        echo "\n  - Site: " . ($lot->site ? $lot->site->name : 'Aucun');
        echo "\n  - URL d'image: " . ($lot->image ? $lot->image : 'Aucune image');
    }
}
    
} catch (\Exception $e) {
    echo "\n- ERREUR lors du rendu de la vue: " . $e->getMessage();
    echo "\n  Fichier: " . $e->getFile() . ":" . $e->getLine();
}

echo "\n\n=== FIN DU DÉBOGAGE ===\n";
