<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'total_area',
        'total_lots',
        'base_price_per_sqm',
        'reservation_fee',
        'membership_fee',
        'payment_plan',
        'amenities',
        'status',
        'image_url',
        'gallery_images',
        'latitude',
        'longitude',
        'is_active',
        'enable_12',
        'enable_24',
        'enable_cash',
        'price_12_months',
        'price_24_months',
        'price_cash',
        'enable_36',
        'price_36_months',
        // Nouveaux champs pour les prix par position
        'price_angle',
        'price_facade',
        'price_interieur',
        'supplement_angle',
        'supplement_facade',
        'enable_payment_cash',
        'enable_payment_1_year',
        'enable_payment_2_years',
        'enable_payment_3_years',
    ];

    protected $casts = [
        'amenities' => 'array',
        'gallery_images' => 'array',
        'total_area' => 'decimal:2',
        'base_price_per_sqm' => 'decimal:2',
        'reservation_fee' => 'decimal:2',
        'membership_fee' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'price_angle' => 'decimal:2',
        'price_facade' => 'decimal:2',
        'price_interieur' => 'decimal:2',
        'supplement_angle' => 'decimal:2',
        'supplement_facade' => 'decimal:2',
        'enable_payment_cash' => 'boolean',
        'enable_payment_1_year' => 'boolean',
        'enable_payment_2_years' => 'boolean',
        'enable_payment_3_years' => 'boolean',
    ];

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class, 'interested_site_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function availableLots(): HasMany
    {
        return $this->hasMany(Lot::class)->where('status', 'disponible');
    }

    public function soldLots(): HasMany
    {
        return $this->hasMany(Lot::class)->where('status', 'vendu');
    }

    public function reservedLots(): HasMany
    {
        return $this->hasMany(Lot::class)->whereIn('status', ['reserve_temporaire', 'reserve']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calcule le prix de base selon la position du lot
     */
    public function getPriceByPosition(string $position): ?float
    {
        return match($position) {
            'angle' => $this->price_angle,
            'facade' => $this->price_facade,
            'interieur' => $this->price_interieur,
            default => null
        };
    }

    /**
     * Vérifie si un plan de paiement est activé
     */
    public function isPaymentPlanEnabled(string $plan): bool
    {
        return match($plan) {
            'cash' => $this->enable_payment_cash,
            '1_year' => $this->enable_payment_1_year,
            '2_years' => $this->enable_payment_2_years,
            '3_years' => $this->enable_payment_3_years,
            default => false
        };
    }

    /**
     * Retourne les plans de paiement disponibles
     */
    public function getAvailablePaymentPlans(): array
    {
        $plans = [];
        if ($this->enable_payment_cash) $plans[] = 'cash';
        if ($this->enable_payment_1_year) $plans[] = '1_year';
        if ($this->enable_payment_2_years) $plans[] = '2_years';
        if ($this->enable_payment_3_years) $plans[] = '3_years';
        return $plans;
    }
}