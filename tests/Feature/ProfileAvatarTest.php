<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_avatar_and_receive_public_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')->post('/api/user/profile/avatar', ['avatar' => $avatar]);

        $response->assertOk()
            ->assertJsonPath('message', 'تصویر پروفایل با موفقیت به‌روزرسانی شد.')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('avatar_url', fn ($url) => str_ends_with($url, '/api/user/profile/avatar'));
        $user->refresh();
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'avatar' => $user->avatar]);
    }

    public function test_old_avatar_is_deleted_when_user_uploads_a_new_one(): void
    {
        Storage::fake('public');
        $oldPath = 'avatars/old.jpg';
        Storage::disk('public')->put($oldPath, 'old');
        $user = User::factory()->create(['avatar' => $oldPath]);

        $this->actingAs($user, 'sanctum')->post('/api/user/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('new.png', 100, 'image/png'),
        ])->assertOk();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_authenticated_user_can_stream_their_avatar(): void
    {
        Storage::fake('public');
        $path = 'avatars/avatar.png';
        Storage::disk('public')->put($path, 'image-content');
        $user = User::factory()->create(['avatar' => $path]);

        $this->actingAs($user, 'sanctum')->get('/api/user/profile/avatar')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}