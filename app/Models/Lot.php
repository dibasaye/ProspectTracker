<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'lot_number',
        'area',
        'position',
        'status',
        'base_price',
        'position_supplement',
        'final_price',
        'client_id',
        'reserved_until',
        'description',
        'coordinates',
        'has_utilities',
        'features',
        'notes',
        'reservation_id',
        // Nouveaux champs pour les plans de paiement
        'price_cash',
        'price_1_year',
        'price_2_years',
        'price_3_years',
        'is_manually_priced',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'base_price' => 'decimal:2',
        'position_supplement' => 'decimal:2',
        'final_price' => 'decimal:2',
        'reserved_until' => 'datetime',
        'coordinates' => 'array',
        'features' => 'array',
        'has_utilities' => 'boolean',
        'price_cash' => 'decimal:2',
        'price_1_year' => 'decimal:2',
        'price_2_years' => 'decimal:2',
        'price_3_years' => 'decimal:2',
        'is_manually_priced' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'client_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

   public function reservation(): HasOne
{
    return $this->hasOne(Reservation::class)->latestOfMany(); // 👈 Prend la dernière réservation si plusieurs
}


    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'disponible');
    }

    public function scopeReserved($query)
    {
        return $query->whereIn('status', ['reserve_temporaire', 'reserve']);
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'vendu');
    }

    public function scopeBySite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'disponible';
    }

    public function isReserved(): bool
    {
        return in_array($this->status, ['reserve_temporaire', 'reserve']);
    }

    public function isSold(): bool
    {
        return $this->status === 'vendu';
    }

    public function getStatusColorAttribute()
    {
        if (empty($this->status)) {
            return '#6c757d';  // gris par défaut si le statut est vide
        }
        
        return match($this->status) {
            'disponible' => '#28a745',           // vert
            'reserve_temporaire' => '#ffc107',   // jaune/orange
            'reserve' => '#fd7e14',              // orange foncé
            'vendu' => '#dc3545',                // rouge
            default => '#6c757d',                // gris (fallback)
        };
    }

    public function getStatusLabelAttribute()
    {
        if (empty($this->status)) {
            return 'Inconnu';  // Valeur par défaut si le statut est vide
        }
        
        return match($this->status) {
            'disponible' => 'Disponible',
            'reserve_temporaire' => 'Réservation temporaire',
            'reserve' => 'Réservé',
            'vendu' => 'Vendu',
            default => 'Inconnu',
        };
    }

    /**
     * Calcule automatiquement les prix selon les plans de paiement
     */
    public function calculatePrices(): void
    {
        if ($this->is_manually_priced) {
            return; // Ne pas recalculer si les prix sont manuels
        }

        $basePrice = $this->site->getPriceByPosition($this->position);
        
        if (!$basePrice) {
            return;
        }

        // Prix au comptant (prix de base)
        $this->price_cash = $basePrice;
        
        // Prix avec échelonnement (majoration selon la durée)
        $this->price_1_year = $basePrice * 1.05; // +5% pour 1 an
        $this->price_2_years = $basePrice * 1.10; // +10% pour 2 ans
        $this->price_3_years = $basePrice * 1.15; // +15% pour 3 ans
    }

    /**
     * Retourne le prix selon le plan de paiement
     */
    public function getPriceByPaymentPlan(string $plan): ?float
    {
        return match($plan) {
            'cash' => $this->price_cash,
            '1_year' => $this->price_1_year,
            '2_years' => $this->price_2_years,
            '3_years' => $this->price_3_years,
            default => null
        };
    }

    /**
     * Retourne le label de la position
     */
    public function getPositionLabelAttribute(): string
    {
        return match($this->position) {
            'angle' => 'Angle',
            'facade' => 'Façade', 
            'interieur' => 'Intérieur',
            default => 'Non défini',
        };
    }

    /**
     * Sauvegarde avec calcul automatique des prix
     */
    public function save(array $options = []): bool
    {
        $this->calculatePrices();
        return parent::save($options);
    }

    
}