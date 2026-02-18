<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded=[];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'external_token_expires_at' => 'datetime',
            'is_root' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Profil root = accès global (toutes les données en base, aucune restriction org/owner).
     * Défini par le flag is_root ou par le rôle ASACI main_office_admin (insensible à la casse).
     */
    public function isRoot(): bool
    {
        if (! empty($this->is_root)) {
            return true;
        }

        $code = is_string($this->user_role_code) ? strtolower(trim($this->user_role_code)) : '';
        $name = is_string($this->user_role_name) ? strtolower(trim($this->user_role_name)) : '';

        return $code === 'main_office_admin'
            || $name === 'main_office_admin'
            || str_contains($code, 'main_office_admin')
            || str_contains($name, 'main_office_admin');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function currentOrganization(): ?Organization
    {
        return $this->organizations->first();
    }

    public function isAdminOf(Organization $organization): bool
    {
        $pivot = $this->organizations()->where('organization_id', $organization->id)->first()?->pivot;

        return $pivot && $pivot->role === 'admin';
    }
}
