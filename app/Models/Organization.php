<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'employee_count_range',
        'referral_source',
        'industry',
        'certy_ia_enabled',
    ];

    /** Certy IA activé par défaut pour les nouvelles organisations quand la fonctionnalité est activée au niveau app. */
    protected $attributes = [
        'certy_ia_enabled' => true,
    ];

    protected function casts(): array
    {
        return [
            'certy_ia_enabled' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function bordereaux(): HasMany
    {
        return $this->hasMany(Bordereau::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'organization_id');
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public static function employeeCountRanges(): array
    {
        return [
            '1-10' => '1-10 employés',
            '11-50' => '11-50 employés',
            '51-200' => '51-200 employés',
            '201+' => '201+ employés',
        ];
    }

    public static function referralSources(): array
    {
        return [
            'google' => 'Google / recherche',
            'bouche_a_oreille' => 'Bouche à oreille',
            'reseaux_sociaux' => 'Réseaux sociaux',
            'recommandation' => 'Recommandation',
            'pub' => 'Publicité',
            'autre' => 'Autre',
        ];
    }

    public static function industries(): array
    {
        return [
            'cabinet' => 'Cabinet (comptable, avocat, etc.)',
            'ecole' => 'École / Éducation',
            'ong' => 'ONG / Association',
            'sante' => 'Santé',
            'commerce' => 'Commerce',
            'autre' => 'Autre',
        ];
    }
}
