<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdvertisementResource;
use App\Http\Requests\StoreAdvertisementRequest;
use App\Models\Advertisement;
use App\Models\BankPlan;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $startedAt = microtime(true);
        $filters = $request->validate([
            'type' => ['nullable', 'in:supply,demand'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'bank_plan_id' => ['nullable', 'integer', 'exists:bank_plans,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'loan_amount_min' => ['nullable', 'numeric', 'min:0'],
            'loan_amount_max' => ['nullable', 'numeric', 'gte:loan_amount_min'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'string', 'max:30'],
        ]);

        $minAmount = $filters['min_amount'] ?? $filters['loan_amount_min'] ?? null;
        $maxAmount = $filters['max_amount'] ?? $filters['loan_amount_max'] ?? null;

        $query = Advertisement::query()
            ->with(['bank', 'bankPlan', 'location.parent', 'user'])
            ->whereIn('status', [Advertisement::STATUS_PUBLISHED, 'approved'])
            ->whereHas('bank', fn ($bankQuery) => $bankQuery->where('is_active', true));

        $query->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type));
        $query->when($filters['bank_id'] ?? null, fn ($query, $bankId) => $query->where('bank_id', $bankId));
        $query->when($filters['bank_plan_id'] ?? null, fn ($query, $planId) => $query->where('bank_plan_id', $planId));
        $query->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId));
        $query->when($filters['location_ids'] ?? null, fn ($query, $locationIds) => $query->whereIn('location_id', $locationIds));
        $query->when($minAmount !== null, fn ($query) => $query->where('loan_amount', '>=', $minAmount));
        $query->when($maxAmount !== null, fn ($query) => $query->where('loan_amount', '<=', $maxAmount));
        $query->when($filters['min_price'] ?? null, fn ($query, $price) => $query->where('transfer_price', '>=', $price));
        $query->when($filters['max_price'] ?? null, fn ($query, $price) => $query->where('transfer_price', '<=', $price));
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhereHas('bankPlan', fn ($planQuery) => $planQuery->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('location', fn ($locationQuery) => $locationQuery->where('name', 'like', "%{$search}%"))
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $advertisements = $query->applySorting($filters['sort'] ?? null)->paginate(12)->withQueryString();

        Log::info('Public advertisements listed', [
            'function' => __METHOD__,
            'user_id' => $request->user()?->id,
            'payload' => $filters,
            'trace' => null,
            'result_count' => $advertisements->count(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);

        return AdvertisementResource::collection($advertisements);
    }

    public function show(Request $request, int $id)
    {
        $advertisement = Advertisement::query()
            ->with(['bank', 'bankPlan', 'location.parent', 'user'])
            ->whereIn('status', [Advertisement::STATUS_PUBLISHED, 'approved'])
            ->whereHas('bank', fn ($bankQuery) => $bankQuery->where('is_active', true))
            ->findOrFail($id);

        Log::info('Public advertisement viewed', [
            'function' => __METHOD__,
            'user_id' => $request->user()?->id,
            'payload' => ['advertisement_id' => $advertisement->id],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return new AdvertisementResource($advertisement);
    }

    public function contact(Request $request, Advertisement $advertisement)
    {
        $user = $request->user('sanctum');

        Log::info('Advertisement contact requested', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => ['advertisement_id' => $advertisement->id],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['advertisement_id' => $advertisement->id, 'mobile' => $advertisement->user?->mobile]);
    }

    public function userAds(Request $request)
    {
        $user = $request->user('sanctum');
        $filters = $request->validate(['status' => ['nullable', 'in:pending_approval,published,rejected,expired,handed_over,pending,approved,closed']]);
        $advertisements = Advertisement::with(['bank', 'bankPlan', 'location.parent', 'user'])
            ->where('user_id', $user->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status === 'pending' ? Advertisement::STATUS_PENDING_APPROVAL : ($status === 'approved' ? Advertisement::STATUS_PUBLISHED : ($status === 'closed' ? Advertisement::STATUS_HANDED_OVER : $status))))
            ->latest()
            ->get();

        Log::info('User advertisements listed', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
            'result_count' => $advertisements->count(),
        ]);

        return AdvertisementResource::collection($advertisements);
    }

    public function store(StoreAdvertisementRequest $request)
    {
        $user = $request->user('sanctum');
        $payload = $request->validated();

        $advertisement = Advertisement::create([
            ...$payload,
            'user_id' => $user->id,
            'status' => $request->has('transfer_price') ? 'pending' : Advertisement::STATUS_PENDING_APPROVAL,
            'transfer_price' => $payload['assignment_price'],
            'interest_rate' => $payload['profit_rate'],
        ]);

        Log::info('Advertisement created', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => ['advertisement_id' => $advertisement->id, 'status' => $advertisement->status],
            'trace' => null,
        ]);

        return (new AdvertisementResource($advertisement->load(['bank', 'bankPlan', 'location.parent', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreAdvertisementRequest $request, Advertisement $advertisement)
    {
        $user = $request->user('sanctum');
        Gate::forUser($user)->authorize('update', $advertisement);
        $advertisement->update([...$request->validated(), 'status' => Advertisement::STATUS_PENDING_APPROVAL, 'rejection_reason' => null]);

        Log::info('Advertisement updated and resubmitted', ['function' => __METHOD__, 'user_id' => $user->id, 'payload' => ['advertisement_id' => $advertisement->id], 'trace' => $request->header('X-Request-Id')]);

        return new AdvertisementResource($advertisement->fresh()->load(['bank', 'bankPlan', 'location.parent', 'user']));
    }

    public function changeStatus(Request $request, Advertisement $advertisement)
    {
        $user = $request->user('sanctum');
        Gate::forUser($user)->authorize('changeStatus', $advertisement);
        $payload = $request->validate(['status' => ['required', 'in:handed_over']]);
        $advertisement->update(['status' => $payload['status']]);

        Log::info('Advertisement status changed', ['function' => __METHOD__, 'user_id' => $user->id, 'payload' => ['advertisement_id' => $advertisement->id, 'status' => $payload['status']], 'trace' => $request->header('X-Request-Id')]);

        return new AdvertisementResource($advertisement->fresh()->load(['bank', 'bankPlan', 'location.parent', 'user']));
    }

    public function destroy(Request $request, Advertisement $advertisement)
    {
        $user = $request->user('sanctum');
        Gate::forUser($user)->authorize('delete', $advertisement);
        $advertisement->delete();

        Log::info('Advertisement soft deleted', ['function' => __METHOD__, 'user_id' => $user->id, 'payload' => ['advertisement_id' => $advertisement->id], 'trace' => $request->header('X-Request-Id')]);

        return response()->json(['message' => 'آگهی حذف شد.']);
    }
}