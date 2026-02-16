<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'code', 'logo_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function bordereaux(): HasMany
    {
        return $this->hasMany(Bordereau::class);
    }

}
