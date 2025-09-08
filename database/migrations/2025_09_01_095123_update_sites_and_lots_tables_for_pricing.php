<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Suppression de l'ancien champ de prix au m²
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('base_price_per_sqm');
        });

        // Ajout des nouveaux champs pour les prix fixes par position
        Schema::table('sites', function (Blueprint $table) {
            // Prix pour les lots en angle
            $table->decimal('angle_price', 15, 2)->after('total_lots');
            // Prix pour les lots en façade
            $table->decimal('facade_price', 15, 2)->after('angle_price');
            // Prix pour les lots intérieurs
            $table->decimal('interior_price', 15, 2)->after('facade_price');
            
            // Champs pour les options de paiement
            $table->decimal('one_year_price', 15, 2)->nullable()->after('interior_price');
            $table->decimal('two_years_price', 15, 2)->nullable()->after('one_year_price');
            $table->decimal('three_years_price', 15, 2)->nullable()->after('two_years_price');
        });

        // Ajout des champs pour les prix de paiement échelonné dans la table lots
        Schema::table('lots', function (Blueprint $table) {
            $table->decimal('one_year_price', 15, 2)->nullable()->after('final_price');
            $table->decimal('two_years_price', 15, 2)->nullable()->after('one_year_price');
            $table->decimal('three_years_price', 15, 2)->nullable()->after('two_years_price');
            
            // Ajout d'un champ pour le type de prix (fixe ou au m² - pour rétrocompatibilité)
            $table->enum('pricing_type', ['fixed', 'per_sqm'])->default('fixed')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Suppression des champs ajoutés à la table lots
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['one_year_price', 'two_years_price', 'three_years_price', 'pricing_type']);
        });

        // Suppression des champs ajoutés à la table sites
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'angle_price', 'facade_price', 'interior_price',
                'one_year_price', 'two_years_price', 'three_years_price'
            ]);
            
            // Récupération de l'ancien champ
            $table->decimal('base_price_per_sqm', 10, 2)->after('total_lots');
        });
    }
};
