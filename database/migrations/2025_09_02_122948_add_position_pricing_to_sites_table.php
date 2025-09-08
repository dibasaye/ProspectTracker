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
            // Prix par position des lots
            $table->decimal('price_angle', 15, 2)->nullable()->comment('Prix pour lots en angle');
            $table->decimal('price_facade', 15, 2)->nullable()->comment('Prix pour lots en façade');
            $table->decimal('price_interieur', 15, 2)->nullable()->comment('Prix pour lots intérieurs');
            
            // Suppléments par position
            $table->decimal('supplement_angle', 15, 2)->default(0)->comment('Supplément pour lots en angle');
            $table->decimal('supplement_facade', 15, 2)->default(0)->comment('Supplément pour lots en façade');
            
            // Configuration globale des plans de paiement du site
            $table->boolean('enable_payment_cash')->default(true);
            $table->boolean('enable_payment_1_year')->default(true);
            $table->boolean('enable_payment_2_years')->default(true);
            $table->boolean('enable_payment_3_years')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'price_angle',
                'price_facade', 
                'price_interieur',
                'supplement_angle',
                'supplement_facade',
                'enable_payment_cash',
                'enable_payment_1_year',
                'enable_payment_2_years',
                'enable_payment_3_years'
            ]);
        });
    }
};
