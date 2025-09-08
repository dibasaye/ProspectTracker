<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'type',
        'category',
        'amount',
        'reference',
        'description',
        'payment_id',
        'client_id',
        'site_id',
        'supplier_id',
        'created_by',
        'status',
        'validated_by',
        'validated_at',
        'receipt_path',
        'attachments',
        'account_code',
        'notes'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'validated_at' => 'datetime',
        'attachments' => 'array'
    ];

    /**
     * Relations
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'client_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Générer automatiquement un numéro de transaction
     */
    public static function generateTransactionNumber($type, $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $datePrefix = $date->format('Ymd');
        $typePrefix = strtoupper(substr($type, 0, 3)); // ENC ou DEC
        
        // Compter les transactions existantes pour ce type et cette date
        $count = self::where('type', $type)
                    ->whereDate('transaction_date', $date)
                    ->count() + 1;
        
        return "{$typePrefix}{$datePrefix}" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Scope pour les encaissements
     */
    public function scopeEncaissements($query)
    {
        return $query->where('type', 'encaissement');
    }

    /**
     * Scope pour les décaissements
     */
    public function scopeDecaissements($query)
    {
        return $query->where('type', 'decaissement');
    }

    /**
     * Scope pour les transactions validées
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope pour les transactions en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'encaissement' ? 'Encaissement' : 'Décaissement';
    }

    /**
     * Obtenir le libellé de la catégorie
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'vente_terrain' => 'Vente de terrain',
            'adhesion' => 'Adhésion',
            'reservation' => 'Réservation',
            'mensualite' => 'Mensualité',
            'salaire' => 'Salaire',
            'charge_social' => 'Charge sociale',
            'fourniture' => 'Fourniture',
            'transport' => 'Transport',
            'maintenance' => 'Maintenance',
            'marketing' => 'Marketing',
            'administration' => 'Administration',
            'autre' => 'Autre',
            default => ucfirst($this->category)
        };
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'validated' => 'Validé',
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
            'pending' => '#ffc107',
            'validated' => '#198754',
            'cancelled' => '#dc3545',
            default => '#6c757d'
        };
    }

    /**
     * Obtenir la couleur du type
     */
    public function getTypeColorAttribute(): string
    {
        return $this->type === 'encaissement' ? '#198754' : '#dc3545';
    }

    /**
     * Obtenir l'URL du justificatif
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_path) {
            return null;
        }
        
        return asset('storage/' . $this->receipt_path);
    }

    /**
     * Obtenir le montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Vérifier si la transaction peut être validée
     */
    public function canBeValidated(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Valider la transaction
     */
    public function validate(User $user): bool
    {
        if (!$this->canBeValidated()) {
            return false;
        }

        $this->status = 'validated';
        $this->validated_by = $user->id;
        $this->validated_at = now();
        
        return $this->save();
    }

    /**
     * Annuler la transaction
     */
    public function cancel(): bool
    {
        if ($this->status === 'validated') {
            return false; // Ne peut pas annuler une transaction validée
        }

        $this->status = 'cancelled';
        
        return $this->save();
    }
}