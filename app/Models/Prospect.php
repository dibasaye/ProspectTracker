<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;


class Prospect extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'phone_secondary',
        'email',
        'address',
        'id_document',
        'representative_name',
        'representative_phone',
        'representative_address',
        'status',
        'assigned_to_id',
        'interested_site_id',
        'notes',
        'budget_min',
        'budget_max',
        'contact_date',
        'next_follow_up',
        'preferences',
        'quality_score',
        'last_follow_up',
        'converted_at',
        'last_contacted_at',
        'last_contact_method',
        'last_contact_notes',
        'last_contacted_by_id',
        'last_contacted_at',
        'last_contact_method',
        'last_contact_notes',
        'last_contacted_by_id',
        'adhesion_payment_id',
        'adhesion_payment_status',
        'adhesion_payment_date',
        'adhesion_payment_amount',
        'adhesion_payment_method',
        'adhesion_payment_reference',




    ];

    protected $casts = [
        'preferences' => 'array',
        'contact_date' => 'date',
        'next_follow_up' => 'date',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function interestedSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'interested_site_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'client_id');
    }

    public function contract()
{
    return $this->hasOne(Contract::class, 'client_id');
}

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'client_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'client_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function followUps()
{
    return $this->hasMany(FollowUpAction::class);
}
public function adhesionPayment()
{
    return $this->hasOne(Payment::class, 'client_id')->where('type', 'adhesion');
}

public function markAsReservataire()
{
    $this->status = 'client_reservataire';
    $this->save();
}

public function markAsConverti()
{
    $this->status = 'converti';
    $this->save();
}

public function markAsInteresse()
{
    $this->status = 'interesse';
    $this->save();
}

public function markAsEnRelance()
{
    $this->status = 'en_relance';
    $this->save();
}

public function markAsAbandonne()
{
    $this->status = 'abandonne';
    $this->save();
}

public function isConverti(): bool
{
    return $this->status === 'converti';
}

public function isInteresse(): bool
{
    return $this->status === 'interesse';
}

public function isEnRelance(): bool
{
    return $this->status === 'en_relance';
}

public function isAbandonne(): bool
{
    return $this->status === 'abandonne';
}

public function isNouveau(): bool
{
    return $this->status === 'nouveau';
}

public function isReservataire(): bool
{
    return $this->status === 'client_reservataire';
}

public function getStatusText(): string
{
    return match($this->status) {
        'nouveau' => 'Nouveau',
        'en_relance' => 'En relance',
        'interesse' => 'Intéressé',
        'converti' => 'Converti',
        'abandonne' => 'Abandonné',
        'client_reservataire' => 'Client réservataire',
        default => 'Inconnu'
    };
}

public function reservation()
{
    return $this->hasOne(Reservation::class);
}

public function reservations()
{
    return $this->hasMany(\App\Models\Reservation::class);
}

public function scopeFilter($query, array $filters)
{
    // Implémentez votre logique de filtrage ici
    if (!empty($filters['site_id'])) {
        $query->where('site_id', $filters['site_id']);
    }
    // Ajoutez d'autres filtres selon vos besoins
}


}