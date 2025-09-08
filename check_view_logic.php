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
echo "Prospect ID: " . $prospect->id . "\n";
echo "Nombre de lots chargés: " . $prospect->lots->count() . "\n";

// Vérifier si la collection de lots est vide
if ($prospect->lots->isEmpty()) {
    echo "La collection de lots est VIDE.\n";
    
    // Vérifier si la relation est chargée
    if ($prospect->relationLoaded('lots')) {
        echo "La relation 'lots' est chargée mais la collection est vide.\n";
    } else {
        echo "La relation 'lots' n'est PAS chargée.\n";
    }
    
    // Vérifier directement dans la base de données
    $directCount = $prospect->lots()->count();
    echo "Nombre de lots dans la base de données: $directCount\n";
    
    if ($directCount > 0) {
        echo "ERREUR: La base de données contient $directCount lots, mais la collection est vide.\n";
        
        // Essayer de recharger la relation
        $prospect->load('lots');
        echo "Après rechargement, nombre de lots: " . $prospect->lots->count() . "\n";
    }
} else {
    echo "La collection de lots contient des éléments.\n";
    
    foreach ($prospect->lots as $lot) {
        echo "- Lot #{$lot->id} - Numéro: {$lot->lot_number} - Statut: {$lot->status}\n";
    }
}

// Vérifier la structure de la collection
if ($prospect->lots instanceof \Illuminate\Database\Eloquent\Collection) {
    echo "La relation retourne bien une Collection Eloquent.\n";
} else {
    echo "ATTENTION: La relation ne retourne PAS une Collection Eloquent.\n";
    echo "Type de l'objet: " . get_class($prospect->lots) . "\n";
}
