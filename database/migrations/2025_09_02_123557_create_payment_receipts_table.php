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
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique(); // Numéro unique du bordereau
            $table->date('receipt_date'); // Date du bordereau
            $table->enum('type', ['daily', 'period'])->default('daily'); // Type de bordereau
            
            // Période couverte par le bordereau
            $table->date('period_start');
            $table->date('period_end');
            
            // Informations de total
            $table->decimal('total_amount', 15, 2); // Montant total
            $table->integer('payment_count'); // Nombre de paiements
            
            // Détails par type de paiement
            $table->decimal('adhesion_amount', 15, 2)->default(0);
            $table->integer('adhesion_count')->default(0);
            $table->decimal('reservation_amount', 15, 2)->default(0);
            $table->integer('reservation_count')->default(0);
            $table->decimal('mensualite_amount', 15, 2)->default(0);
            $table->integer('mensualite_count')->default(0);
            
            // Informations de génération
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at');
            
            // Statut et validation
            $table->enum('status', ['draft', 'finalized', 'cancelled'])->default('draft');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();
            
            // Chemin du fichier PDF généré
            $table->string('pdf_path')->nullable();
            
            // Notes et observations
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['receipt_date', 'type']);
            $table->index(['period_start', 'period_end']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};