<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $startedAt = microtime(true);
        $banks = Bank::query()
            ->where('is_active', true)
            ->withCount(['advertisements' => fn ($query) => $query->where('status', Advertisement::STATUS_PUBLISHED)])
            ->orderBy('name')
            ->get();
        $publishedAdvertisementsCount = (int) Advertisement::query()
            ->where('status', Advertisement::STATUS_PUBLISHED)
            ->whereHas('bank', fn ($query) => $query->where('is_active', true))
            ->count();

        Log::info('Active banks listed', [
            'function' => __METHOD__,
            'user_id' => $request->user()?->id,
            'payload' => [],
            'trace' => null,
            'result_count' => $banks->count(),
            'published_advertisements_count' => $publishedAdvertisementsCount,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);

        return response()->json([
            'data' => $banks,
            'meta' => ['published_advertisements_count' => $publishedAdvertisementsCount],
        ]);
    }

    public function plans(Request $request, int $id): JsonResponse
    {
        $startedAt = microtime(true);
        $bank = Bank::query()->where('is_active', true)->findOrFail($id);
        $plans = $bank->plans()->where('is_active', true)->orderBy('title')->get();

        Log::info('Active bank plans listed', [
            'function' => __METHOD__,
            'user_id' => $request->user()?->id,
            'payload' => ['bank_id' => $id],
            'trace' => null,
            'result_count' => $plans->count(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);

        return response()->json(['data' => $plans]);
    }
}