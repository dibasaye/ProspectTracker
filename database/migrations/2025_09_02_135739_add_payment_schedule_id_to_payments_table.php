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
        Schema::table('payments', function (Blueprint $table) {
            // Ajouter la colonne payment_schedule_id pour lier les paiements aux échéances
            $table->unsignedBigInteger('payment_schedule_id')->nullable()->after('contract_id');
            $table->foreign('payment_schedule_id')
                  ->references('id')
                  ->on('payment_schedules')
                  ->onDelete('set null');
            
            // Ajouter aussi created_by et completed_at si elles n'existent pas déjà
            if (!Schema::hasColumn('payments', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_schedule_id']);
            $table->dropColumn('payment_schedule_id');
            
            if (Schema::hasColumn('payments', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            
            if (Schema::hasColumn('payments', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });
    }
};
