<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'organization_id',
        'owner_id',
        'full_name',
        'email',
        'phone',
        'address',
        'postal_address',
        'profession_id',
        'type_assure',
    ];

    public const TYPE_TAPP = 'TAPP';
    public const TYPE_TAPM = 'TAPM';

    /**
     * Périmètre d'accès : root = tous les clients de l'organisation, non-root = clients dont owner_id = user.
     */
    public function scopeAccessibleBy(Builder $query, \App\Models\User $user): Builder
    {
        $org = $user->currentOrganization();
        if (! $org) {
            return $query->whereRaw('1=0');
        }
        $query->where('organization_id', $org->id);
        if (! $user->isRoot()) {
            $query->where('owner_id', $user->id);
        }

        return $query;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
