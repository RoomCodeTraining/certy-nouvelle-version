<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Client extends Model
{
    protected $fillable = [
        'reference',
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

    /** Préfixe et longueur après le tiret : 11 caractères alphanumériques majuscules (ex. CL-A7K9M2X4B1Q). */
    public const REFERENCE_PREFIX = 'CL-';

    public const REFERENCE_SUFFIX_LENGTH = 11;

    /**
     * Génère une référence unique : CL- + 11 caractères alphanumériques majuscules.
     */
    public static function generateUniqueReference(): string
    {
        do {
            $ref = self::REFERENCE_PREFIX . strtoupper(Str::random(self::REFERENCE_SUFFIX_LENGTH));
        } while (self::query()->where('reference', $ref)->exists());

        return $ref;
    }

    /**
     * Périmètre d'accès : root = toutes les données en base (aucune restriction org/owner) ;
     * non-root = clients de son organisation dont owner_id = user.
     */
    public function scopeAccessibleBy(Builder $query, \App\Models\User $user): Builder
    {
        if ($user->isRoot()) {
            return $query;
        }

        $org = $user->currentOrganization();
        if (! $org) {
            return $query->whereRaw('1=0');
        }

        return $query->where('organization_id', $org->id)->where('owner_id', $user->id);
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
