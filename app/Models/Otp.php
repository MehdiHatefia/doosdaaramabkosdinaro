<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'mobile', 'code', 'expires_at', 'consumed_at', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}