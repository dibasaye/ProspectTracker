<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class PaymentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'receipt_date',
        'type',
        'period_start',
        'period_end',
        'total_amount',
        'payment_count',
        'adhesion_amount',
        'adhesion_count',
        'reservation_amount',
        'reservation_count',
        'mensualite_amount',
        'mensualite_count',
        'generated_by',
        'generated_at',
        'status',
        'validated_by',
        'validated_at',
        'pdf_path',
        'notes'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
        'adhesion_amount' => 'decimal:2',
        'reservation_amount' => 'decimal:2',
        'mensualite_amount' => 'decimal:2',
        'generated_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a généré le bordereau
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Relation avec l'utilisateur qui a validé le bordereau
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Relation many-to-many avec les paiements
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_receipt_payments')
                    ->withTimestamps();
    }

    /**
     * Générer automatiquement un numéro de bordereau
     */
    public static function generateReceiptNumber($date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $datePrefix = $date->format('Ymd');
        
        // Compter les bordereaux existants pour cette date
        $count = self::whereDate('receipt_date', $date)->count() + 1;
        
        return "BV{$datePrefix}" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Calculer les totaux depuis les paiements liés
     */
    public function calculateTotals(): void
    {
        $payments = $this->payments;
        
        $this->payment_count = $payments->count();
        $this->total_amount = $payments->sum('amount');
        
        // Calculer par type de paiement
        $this->adhesion_count = $payments->where('type', 'adhesion')->count();
        $this->adhesion_amount = $payments->where('type', 'adhesion')->sum('amount');
        
        $this->reservation_count = $payments->where('type', 'reservation')->count();
        $this->reservation_amount = $payments->where('type', 'reservation')->sum('amount');
        
        $this->mensualite_count = $payments->where('type', 'mensualite')->count();
        $this->mensualite_amount = $payments->where('type', 'mensualite')->sum('amount');
    }

    /**
     * Vérifier si le bordereau peut être finalisé
     */
    public function canBeFinalized(): bool
    {
        return $this->status === 'draft' && $this->payment_count > 0;
    }

    /**
     * Finaliser le bordereau
     */
    public function finalize(User $user): bool
    {
        if (!$this->canBeFinalized()) {
            return false;
        }

        $this->status = 'finalized';
        $this->validated_by = $user->id;
        $this->validated_at = now();
        
        return $this->save();
    }

    /**
     * Obtenir l'URL du PDF
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }
        
        return asset('storage/' . $this->pdf_path);
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'finalized' => 'Finalisé',
            'cancelled' => 'Annulé',
            default => 'Inconnu'
        };
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => '#6c757d',
            'finalized' => '#198754',
            'cancelled' => '#dc3545',
            default => '#6c757d'
        };
    }
}