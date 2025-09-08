<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'profile_image_url',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function assignedProspects()
    {
        return $this->hasMany(Prospect::class, 'assigned_to_id');
    }

    public function confirmedPayments()
    {
        return $this->hasMany(Payment::class, 'confirmed_by');
    }

    public function generatedContracts()
    {
        return $this->hasMany(Contract::class, 'generated_by');
    }

    public function signedContracts()
    {
        return $this->hasMany(Contract::class, 'signed_by_agent');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Vérifie si l'utilisateur est un administrateur
     */
    public function isAdmin(): bool
    {
        return $this->role === 'administrateur' || $this->hasRole('administrateur');
    }
    
    /**
     * Vérifie si l'utilisateur est un caissier
     */
    public function isCaissier(): bool
    {
        return $this->role === 'caissier' || $this->hasRole('caissier');
    }
    
    /**
     * Vérifie si l'utilisateur est un responsable commercial
     */
    public function isResponsableCommercial(): bool
    {
        return $this->role === 'responsable_commercial' || $this->hasRole('responsable_commercial');
    }
    
    /**
     * Vérifie si l'utilisateur est un commercial
     */
    public function isCommercial(): bool
    {
        return $this->role === 'commercial' || $this->hasRole('commercial');
    }
    
    /**
     * Vérifie si l'utilisateur peut valider des paiements
     */
    public function canValidatePayments(): bool
    {
        return $this->isAdmin() || $this->isCaissier() || $this->isResponsableCommercial();
    }

    /**
     * Vérifie si l'utilisateur est un manager (alias de isResponsableCommercial)
     */
    public function isManager(): bool
    {
        return $this->isResponsableCommercial();
    }

    /**
     * Vérifie si l'utilisateur est un agent commercial (alias de isCommercial)
     */
    public function isAgent(): bool
    {
        return $this->isCommercial();
    }

    /**
     * Relation avec les suivis d'actions
     */
    public function followUps()
    {
        return $this->hasMany(FollowUpAction::class);
    }
}
