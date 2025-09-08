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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique(); // Numéro unique de transaction
            $table->date('transaction_date'); // Date de la transaction
            $table->enum('type', ['encaissement', 'decaissement']); // Type de transaction
            $table->enum('category', [
                'vente_terrain', 'adhesion', 'reservation', 'mensualite', // Encaissements
                'salaire', 'charge_social', 'fourniture', 'transport', 'maintenance', 
                'marketing', 'administration', 'autre' // Décaissements
            ]);
            
            // Montant et référence
            $table->decimal('amount', 15, 2); // Montant de la transaction
            $table->string('reference')->nullable(); // Référence externe (chèque, virement, etc.)
            $table->text('description'); // Description de la transaction
            
            // Liens avec d'autres entités
            $table->foreignId('payment_id')->nullable()->constrained('payments'); // Lien avec paiement si applicable
            $table->foreignId('client_id')->nullable()->constrained('prospects'); // Lien avec client si applicable
            $table->foreignId('site_id')->nullable()->constrained('sites'); // Lien avec site si applicable
            $table->foreignId('supplier_id')->nullable()->constrained('users'); // Fournisseur/Bénéficiaire (utilisateur)
            
            // Informations de validation
            $table->foreignId('created_by')->constrained('users'); // Qui a créé la transaction
            $table->enum('status', ['pending', 'validated', 'cancelled'])->default('pending');
            $table->foreignId('validated_by')->nullable()->constrained('users'); // Qui a validé
            $table->timestamp('validated_at')->nullable(); // Date de validation
            
            // Justificatifs
            $table->string('receipt_path')->nullable(); // Chemin vers le justificatif
            $table->json('attachments')->nullable(); // Pièces jointes additionnelles
            
            // Informations comptables
            $table->string('account_code')->nullable(); // Code comptable
            $table->text('notes')->nullable(); // Notes internes
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['transaction_date', 'type']);
            $table->index(['category', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};