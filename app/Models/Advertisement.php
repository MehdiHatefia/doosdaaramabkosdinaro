<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Traits\HasJalaliDates;

class Advertisement extends Model
{
    use HasFactory, HasJalaliDates, SoftDeletes;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_HANDED_OVER = 'handed_over';

    protected array $jalaliDateFields = ['created_at', 'updated_at'];

    public const SORT_OPTIONS = [
        'latest' => ['column' => 'created_at', 'direction' => 'desc'],
        'amount_desc' => ['column' => 'loan_amount', 'direction' => 'desc'],
        'amount_asc' => ['column' => 'loan_amount', 'direction' => 'asc'],
        'price_asc' => ['column' => 'assignment_price', 'direction' => 'asc'],
        'price_desc' => ['column' => 'assignment_price', 'direction' => 'desc'],
        'rate_asc' => ['column' => 'profit_rate', 'direction' => 'asc'],
    ];

    protected $fillable = [
        'user_id',
        'bank_id',
        'bank_plan_id',
        'location_id',
        'title',
        'type',
        'loan_amount',
        'assignment_price',
        'profit_rate',
        'installment_count',
        'description',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'loan_amount' => 'integer',
            'assignment_price' => 'integer',
            'profit_rate' => 'decimal:2',
            'installment_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function scopeApplySorting(Builder $query, ?string $sort = null): Builder
    {
        $selectedSort = array_key_exists($sort ?? '', self::SORT_OPTIONS) ? $sort : 'latest';
        $sorting = self::SORT_OPTIONS[$selectedSort];

        $query->orderBy($sorting['column'], $sorting['direction']);

        Log::info('Advertisement sorting applied', [
            'function' => __METHOD__,
            'user_id' => null,
            'payload' => ['sort' => $selectedSort],
            'trace' => null,
            'query_id' => spl_object_id($query),
        ]);

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankPlan(): BelongsTo
    {
        return $this->belongsTo(BankPlan::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')
            ->withTimestamps();
    }
}