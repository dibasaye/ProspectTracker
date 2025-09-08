<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('lot_id')->nullable(); // Peut être null pour les contrats groupés
            $table->enum('status', ['brouillon', 'genere', 'signe', 'archive'])->default('brouillon');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->integer('payment_duration_months');
            $table->decimal('monthly_payment', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('signature_date')->nullable();
            $table->string('contract_file_url')->nullable(); // path to generated contract PDF
            $table->json('terms_and_conditions')->nullable(); // JSON for custom terms
            $table->text('special_clauses')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->unsignedBigInteger('signed_by_client')->nullable();
            $table->unsignedBigInteger('signed_by_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('prospects')->onDelete('cascade');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('signed_by_client')->references('id')->on('prospects')->onDelete('set null');
            $table->foreign('signed_by_agent')->references('id')->on('users')->onDelete('set null');
            $table->index(['client_id', 'status']);
            $table->index('signature_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};