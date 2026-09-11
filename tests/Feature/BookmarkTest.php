<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_bookmarks(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $otherUser = User::factory()->create(['is_verified' => true]);
        $ownedAdvertisement = Advertisement::factory()->create();
        $otherAdvertisement = Advertisement::factory()->create();

        Bookmark::create(['user_id' => $user->id, 'advertisement_id' => $ownedAdvertisement->id]);
        Bookmark::create(['user_id' => $otherUser->id, 'advertisement_id' => $otherAdvertisement->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/bookmarks');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedAdvertisement->id);
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $otherUser->id,
            'advertisement_id' => $otherAdvertisement->id,
        ]);
    }

    public function test_user_without_bookmarks_receives_an_empty_array(): void
    {
        $user = User::factory()->create(['is_verified' => true]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/bookmarks');

        $response->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_toggle_bookmark_adds_and_removes_only_the_current_users_record(): void
    {
        $user = User::factory()->create(['is_verified' => true]);
        $advertisement = Advertisement::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/bookmarks/{$advertisement->id}")
            ->assertOk()
            ->assertJsonPath('bookmarked', true);
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'advertisement_id' => $advertisement->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/bookmarks/{$advertisement->id}")
            ->assertOk()
            ->assertJsonPath('bookmarked', false);
        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'advertisement_id' => $advertisement->id,
        ]);
    }
}
