<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'installment_number',
        'amount',
        'due_date',
        'is_paid',
        'paid_date',
        'payment_id',
        'payment_method',
        'notes',
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
        'due_date' => 'date',
        'paid_date' => 'date',
        'is_paid' => 'boolean',
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
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
     * Vérifie si l'échéance peut être validée par le caissier
     */
    public function canBeValidatedByCaissier(): bool
    {
        return !$this->caissier_validated && 
               !$this->responsable_validated && 
               !$this->admin_validated;
    }
    
    /**
     * Vérifie si l'échéance peut être validée par le responsable
     */
    public function canBeValidatedByResponsable(): bool
    {
        return $this->caissier_validated && 
               !$this->responsable_validated && 
               !$this->admin_validated;
    }
    
    /**
     * Vérifie si l'échéance peut être validée par l'admin
     */
    public function canBeValidatedByAdmin(): bool
    {
        return $this->caissier_validated && 
               $this->responsable_validated && 
               !$this->admin_validated;
    }
    
    /**
     * Vérifie si l'échéance est complètement validée
     */
    public function isFullyValidated(): bool
    {
        return $this->caissier_validated && 
               $this->responsable_validated && 
               $this->admin_validated;
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_paid', false)
                    ->where('due_date', '<', now());
    }

    public function scopeUpcoming($query, $days = 30)
    {
        return $query->where('is_paid', false)
                    ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }
}