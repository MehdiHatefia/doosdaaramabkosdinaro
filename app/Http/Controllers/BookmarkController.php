<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdvertisementResource;
use App\Models\Advertisement;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        Log::info('[Bookmarks:fetch] Bookmark fetch started', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
        ]);

        $bookmarks = $user->bookmarks()
            ->with(['advertisement.bank', 'advertisement.bankPlan', 'advertisement.location.parent', 'advertisement.user'])
            ->latest()
            ->get();
        $advertisements = $bookmarks->pluck('advertisement')->filter()->values();

        Log::info('[Bookmarks:fetch] Bookmark fetch completed', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => [],
            'trace' => $request->header('X-Request-Id'),
            'result_count' => $advertisements->count(),
        ]);

        return AdvertisementResource::collection($advertisements);
    }

    public function toggle(Request $request, int $advertisementId)
    {
        $user = $request->user('sanctum');
        $advertisement = Advertisement::query()
            ->whereKey($advertisementId)
            ->whereIn('status', [Advertisement::STATUS_PUBLISHED, 'approved'])
            ->firstOrFail();

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('advertisement_id', $advertisement->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $bookmarked = false;
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'advertisement_id' => $advertisement->id,
            ]);
            $bookmarked = true;
        }

        Log::info('[Bookmarks:toggle] Bookmark state changed', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => ['advertisement_id' => $advertisement->id, 'bookmarked' => $bookmarked],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json([
            'bookmarked' => $bookmarked,
            'advertisement_id' => $advertisement->id,
        ]);
    }

    public function toggleByModel(Request $request, Advertisement $advertisement)
    {
        return $this->toggle($request, $advertisement->id);
    }

    public function destroy(Request $request, int $advertisementId)
    {
        $user = $request->user('sanctum');
        $deleted = Bookmark::where('user_id', $user->id)
            ->where('advertisement_id', $advertisementId)
            ->delete();

        Log::info('[Bookmarks:delete] Bookmark removed', [
            'function' => __METHOD__,
            'user_id' => $user->id,
            'payload' => ['advertisement_id' => $advertisementId, 'deleted' => $deleted > 0],
            'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['bookmarked' => false, 'advertisement_id' => $advertisementId]);
    }
}
