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
        Schema::table('payment_schedules', function (Blueprint $table) {
            // Colonnes pour la validation en 4 étapes
            $table->string('validation_status')->default('pending')->after('notes');
            
            // Validation par le caissier
            $table->boolean('caissier_validated')->default(false)->after('validation_status');
            $table->foreignId('caissier_validated_by')->nullable()->constrained('users')->after('caissier_validated');
            $table->timestamp('caissier_validated_at')->nullable()->after('caissier_validated_by');
            $table->text('caissier_notes')->nullable()->after('caissier_validated_at');
            $table->decimal('caissier_amount_received', 15, 2)->nullable()->after('caissier_notes');
            $table->string('payment_proof_path')->nullable()->after('caissier_amount_received');
            
            // Validation par le responsable
            $table->boolean('responsable_validated')->default(false)->after('payment_proof_path');
            $table->foreignId('responsable_validated_by')->nullable()->constrained('users')->after('responsable_validated');
            $table->timestamp('responsable_validated_at')->nullable()->after('responsable_validated_by');
            $table->text('responsable_notes')->nullable()->after('responsable_validated_at');
            
            // Validation par l'admin
            $table->boolean('admin_validated')->default(false)->after('responsable_notes');
            $table->foreignId('admin_validated_by')->nullable()->constrained('users')->after('admin_validated');
            $table->timestamp('admin_validated_at')->nullable()->after('admin_validated_by');
            $table->text('admin_notes')->nullable()->after('admin_validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            // Suppression des colonnes de validation
            $table->dropColumn([
                'validation_status',
                'caissier_validated',
                'caissier_validated_by',
                'caissier_validated_at',
                'caissier_notes',
                'caissier_amount_received',
                'payment_proof_path',
                'responsable_validated',
                'responsable_validated_by',
                'responsable_validated_at',
                'responsable_notes',
                'admin_validated',
                'admin_validated_by',
                'admin_validated_at',
                'admin_notes'
            ]);
        });
    }
};
