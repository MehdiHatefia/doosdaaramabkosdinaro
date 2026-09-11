<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\HasJalaliDates;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasJalaliDates;

    protected array $jalaliDateFields = ['birth_date'];

    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(UserVerification::class);
    }

    public function verification(): HasOne
    {
        return $this->hasOne(UserVerification::class)->latestOfMany();
    }

        public function province(): BelongsTo
        {
            return $this->belongsTo(Location::class, 'province_id');
        }

        public function city(): BelongsTo
        {
            return $this->belongsTo(Location::class, 'city_id');
        }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'mobile',
        'email',
        'national_code',
        'birth_date',
        'iban',
        'avatar',
        'province_id',
        'city_id',
        'show_phone_publicly',
        'role',
        'password',
        'is_verified',
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
            'birth_date' => 'date:Y-m-d',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'show_phone_publicly' => 'boolean',
        ];
    }
}
