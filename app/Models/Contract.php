<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'client_id',
        'site_id',
        'lot_id',
        'status',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_duration_months',
        'monthly_payment',
        'start_date',
        'end_date',
        'signature_date',
        'contract_file_url',
        'terms_and_conditions',
        'special_clauses',
        'generated_by',
        'signed_by_client',
        'signed_by_agent',
        'notes',
        'content',
        'is_editable',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signature_date' => 'date',
        'validated_at' => 'datetime',
        'terms_and_conditions' => 'array',
        'is_editable' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'client_id');
    }
    
    /**
     * Get the payments for the contract.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function signedByClient(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'signed_by_client');
    }

    public function signedByAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_agent');
    }
    
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signe');
    }

    public function isSigned(): bool
    {
        return $this->status === 'signe';
    }

    public function isDraft(): bool
    {
        return $this->status === 'brouillon';
    }
}