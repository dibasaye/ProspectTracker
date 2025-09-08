<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'site_id',
        'lot_id',
        'contract_id',
        'payment_schedule_id',
        'type',
        'amount',
        'payment_method',
        'reference_number',
        'description',
        'payment_date',
        'due_date',
        'receipt_url',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'notes',
        'created_by',
        'completed_at',
        // Validation en 4 étapes
        'validation_status',
        // Caissier
        'caissier_validated',
        'caissier_validated_by',
        'caissier_validated_at',
        'caissier_notes',
        'caissier_amount_received',
        'payment_proof_path',
        // Responsable
        'responsable_validated',
        'responsable_validated_by',
        'responsable_validated_at',
        'responsable_notes',
        // Admin
        'admin_validated',
        'admin_validated_by',
        'admin_validated_at',
        'admin_notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        // Validation en 4 étapes
        'caissier_validated' => 'boolean',
        'caissier_amount_received' => 'decimal:2',
        'caissier_validated_at' => 'datetime',
        'responsable_validated' => 'boolean',
        'responsable_validated_at' => 'datetime',
        'admin_validated' => 'boolean',
        'admin_validated_at' => 'datetime',
        'caissier_validated_at' => 'datetime',
        'caissier_amount_received' => 'decimal:2',
        'manager_validated' => 'boolean',
        'manager_validated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Prospect::class, 'client_id');
    }
    
    /**
     * Relation avec l'utilisateur qui a validé en tant que caissier
     */
    public function caissierValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_validated_by');
    }
    
    /**
     * Relation avec l'utilisateur qui a validé en tant que responsable
     */
    public function responsableValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_validated_by');
    }
    
    /**
     * Alias pour la rétrocompatibilité (manager = responsable)
     */
    public function managerValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_validated_by');
    }
    
    /**
     * Relation avec l'utilisateur qui a validé en tant qu'admin
     */
    public function adminValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_validated_by');
    }
    
    /**
     * @deprecated Utiliser adminValidatedBy() à la place
     */
    public function admin()
    {
        return $this->adminValidatedBy();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
    
    /**
     * Relation avec le contrat
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
    
    /**
     * Relation avec l'échéance de paiement
     */
    public function paymentSchedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class);
    }

    /**
     * Get all cash transactions associated with this payment.
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'payment_id');
    }
    
    /**
     * Relation avec l'utilisateur qui a créé le paiement
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation many-to-many avec les bordereaux de versement
     */
    public function paymentReceipts()
    {
        return $this->belongsToMany(PaymentReceipt::class, 'payment_receipt_payments')
                    ->withTimestamps();
    }

    /**
     * Obtenir l'URL du justificatif de validation
     */
    public function getValidationProofUrlAttribute()
    {
        if (!$this->validation_proof_path) {
            return null;
        }
        return asset('storage/' . $this->validation_proof_path);
    }

    /**
     * Obtenir l'URL du justificatif de paiement
     */
    public function getPaymentProofUrlAttribute()
    {
        \Log::info('Payment proof path:', [
            'payment_id' => $this->id,
            'payment_proof_path' => $this->payment_proof_path,
            'full_path' => $this->payment_proof_path ? storage_path('app/public/' . $this->payment_proof_path) : null,
            'file_exists' => $this->payment_proof_path ? file_exists(storage_path('app/public/' . $this->payment_proof_path)) : false
        ]);

        if (!$this->payment_proof_path) {
            \Log::warning('Aucun chemin de justificatif trouvé pour le paiement', ['payment_id' => $this->id]);
            return null;
        }
        
        $url = asset('storage/' . $this->payment_proof_path);
        \Log::info('URL générée pour le justificatif:', ['url' => $url]);
        
        return $url;
    }

    public function scopeConfirmed($query)
    {
        return $query->where('validation_status', 'fully_validated');
    }

    public function scopePending($query)
    {
        return $query->where('validation_status', 'pending');
    }

    public function scopeCaissierValidated($query)
    {
        return $query->where('caissier_validated', true);
    }

    public function scopeManagerValidated($query)
    {
        return $query->where('manager_validated', true);
    }

    public function scopeFullyValidated($query)
    {
        return $query->where('validation_status', 'fully_validated');
    }

    public function scopeWaitingForManager($query)
    {
        return $query->where('validation_status', 'caissier_validated');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }


    public function getFormattedAmountAttribute(): string
{
    return number_format($this->amount, 0, ',', ' ') . ' FCFA';
}

public function getInvoiceUrlAttribute(): string
{
    return route('payments.facture', $this);
}

// Méthodes pour la validation en 4 étapes
public function canBeValidatedByCaissier(): bool
{
    return $this->validation_status === 'pending';
}

public function canBeValidatedByResponsable(): bool
{
    return $this->validation_status === 'caissier_validated';
}

public function canBeValidatedByAdmin(): bool
{
    return $this->validation_status === 'responsable_validated';
}

// Méthodes d'état
public function isPending(): bool
{
    return $this->validation_status === 'pending';
}

public function isCaissierValidated(): bool
{
    return $this->validation_status === 'caissier_validated';
}

public function isResponsableValidated(): bool
{
    return $this->validation_status === 'responsable_validated';
}

public function isAdminValidated(): bool
{
    return $this->validation_status === 'admin_validated';
}

public function isCompleted(): bool
{
    return $this->validation_status === 'completed';
}

public function isRejected(): bool
{
    return $this->validation_status === 'rejected';
}

    /**
     * Obtient le texte du statut de validation avec des détails supplémentaires
     */
    public function getValidationStatusText(): string
    {
        $statusText = match($this->validation_status) {
            'pending' => 'En attente de validation par le caissier',
            'caissier_validated' => 'Validé par le caissier',
            'responsable_validated' => 'Validé par le responsable',
            'admin_validated' => 'Validé par l\'administrateur',
            'completed' => 'Validation complète',
            'rejected' => 'Rejeté',
            default => 'Statut inconnu'
        };
        
        // Ajouter des détails supplémentaires selon le statut
        if ($this->validation_status === 'caissier_validated' && $this->caissierValidatedBy) {
            $statusText .= ' (Par: ' . $this->caissierValidatedBy->name . ')';
        } elseif ($this->validation_status === 'responsable_validated' && $this->responsableValidatedBy) {
            $statusText .= ' (Par: ' . $this->responsableValidatedBy->name . ')';
        } elseif ($this->validation_status === 'admin_validated' && $this->adminValidatedBy) {
            $statusText .= ' (Par: ' . $this->adminValidatedBy->name . ')';
        }
        
        return $statusText;
    }


}