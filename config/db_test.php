<?php

use Illuminate\Support\Facades\DB;

try {
    // Test de connexion à la base de données
    $pdo = DB::connection()->getPdo();
    
    // Récupérer les informations de la base de données
    $databaseName = DB::getDatabaseName();
    $driverName = DB::getDriverName();
    
    // Vérifier si la table contract_lot existe
    $tableExists = DB::getSchemaBuilder()->hasTable('contract_lot');
    
    // Récupérer la structure de la table contract_lot si elle existe
    $tableStructure = [];
    if ($tableExists) {
        $columns = DB::select("PRAGMA table_info(contract_lot)");
        foreach ($columns as $column) {
            $tableStructure[] = [
                'name' => $column->name,
                'type' => $column->type,
                'notnull' => $column->notnull,
                'default' => $column->dflt_value,
                'pk' => $column->pk
            ];
        }
    }
    
    // Retourner les résultats
    return [
        'success' => true,
        'database' => [
            'name' => $databaseName,
            'driver' => $driverName,
            'table_exists' => $tableExists,
            'table_structure' => $tableStructure
        ]
    ];
    
} catch (\Exception $e) {
    // En cas d'erreur, retourner les détails de l'erreur
    return [
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
}
