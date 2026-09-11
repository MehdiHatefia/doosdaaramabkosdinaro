<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankPlan extends Model
{
    use HasFactory;

    protected $fillable = ['bank_id', 'title', 'interest_rate', 'is_active'];

    protected function casts(): array
    {
        return ['interest_rate' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class);
    }
}