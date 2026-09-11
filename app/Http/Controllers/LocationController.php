<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $provinces = Location::query()->provinces()->with('children')->orderBy('name')->get();

        Log::info('Locations provinces listed', [
            'function' => __METHOD__,
            'user_id' => $request->user()?->id,
            'payload' => [],
            'trace' => null,
            'result_count' => $provinces->count(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);

        return response()->json(['data' => $provinces]);
    }

        public function cities(Request $request, int $provinceId): JsonResponse
        {
            $cities = Location::query()->where('parent_id', $provinceId)->orderBy('name')->get();

            Log::info('Locations cities listed', [
                'function' => __METHOD__,
                'user_id' => $request->user()?->id,
                'payload' => ['province_id' => $provinceId],
                'trace' => $request->header('X-Request-Id'),
                'result_count' => $cities->count(),
            ]);

            return response()->json(['data' => $cities]);
        }
}