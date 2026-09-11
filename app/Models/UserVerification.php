<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVerification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'home_phone', 'postal_address', 'residence_document_path',
        'national_code', 'national_card_serial', 'national_card_front_path',
        'national_card_back_path', 'birth_certificate_p1_path',
        'birth_certificate_p2_path', 'job_document_path', 'iban',
        'ownership_confirmed', 'status', 'reviewed_by', 'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'ownership_confirmed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
