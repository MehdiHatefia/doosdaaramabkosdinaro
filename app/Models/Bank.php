<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(BankPlan::class);
    }
}