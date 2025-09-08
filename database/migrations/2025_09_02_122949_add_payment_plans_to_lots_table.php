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
        Schema::table('lots', function (Blueprint $table) {
            // Prix selon les plans de paiement
            $table->decimal('price_cash', 15, 2)->nullable()->comment('Prix au comptant');
            $table->decimal('price_1_year', 15, 2)->nullable()->comment('Prix paiement 1 an');
            $table->decimal('price_2_years', 15, 2)->nullable()->comment('Prix paiement 2 ans');
            $table->decimal('price_3_years', 15, 2)->nullable()->comment('Prix paiement 3 ans');
            
            // Champs additionnels pour la gestion
            $table->text('notes')->nullable()->comment('Notes du lot');
            $table->boolean('is_manually_priced')->default(false)->comment('Prix modifié manuellement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn([
                'price_cash',
                'price_1_year',
                'price_2_years',
                'price_3_years',
                'notes',
                'is_manually_priced'
            ]);
        });
    }
};
