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
        Schema::table('sites', function (Blueprint $table) {
            // Ajouter l'unité de mesure
            $table->enum('area_unit', ['m2', 'hectare', 'are', 'centiare'])
                  ->default('m2')
                  ->after('total_area');
            
            // Ajouter la date de lancement
            $table->date('launch_date')->nullable()->after('total_lots');
            
            // Ajouter les pourcentages personnalisés pour les plans de paiement
            $table->decimal('percentage_1_year', 5, 2)->nullable()->after('enable_payment_1_year');
            $table->decimal('percentage_2_years', 5, 2)->nullable()->after('enable_payment_2_years');
            $table->decimal('percentage_3_years', 5, 2)->nullable()->after('enable_payment_3_years');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'area_unit',
                'launch_date',
                'percentage_1_year',
                'percentage_2_years',
                'percentage_3_years'
            ]);
        });
    }
};