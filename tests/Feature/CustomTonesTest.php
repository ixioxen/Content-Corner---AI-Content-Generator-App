<?php

namespace Tests\Feature;

use App\Models\CustomTone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomTonesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_tone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tones.store'), [
            'name' => 'Friendly',
            'description' => 'Warm and conversational',
            'sample_content' => 'Thanks for stopping by!',
        ]);

        $tone = CustomTone::query()->firstOrFail();

        $response->assertRedirect(route('tones.index'));
        $this->assertSame($user->id, $tone->user_id);
        $this->assertSame('Friendly', $tone->name);
    }

    public function test_user_can_update_and_delete_their_tone(): void
    {
        $user = User::factory()->create();
        $tone = $user->customTones()->create([
            'name' => 'Original',
            'description' => 'Original description',
            'sample_content' => 'Original sample',
        ]);

        $this->actingAs($user)
            ->put(route('tones.update', $tone), [
                'name' => 'Updated',
                'description' => 'Updated description',
                'sample_content' => 'Updated sample',
            ])
            ->assertRedirect(route('tones.index'));

        $this->assertDatabaseHas('custom_tones', [
            'id' => $tone->id,
            'name' => 'Updated',
        ]);

        $this->delete(route('tones.destroy', $tone))
            ->assertRedirect(route('tones.index'));

        $this->assertSoftDeleted('custom_tones', ['id' => $tone->id]);
        $this->assertDatabaseCount('custom_tone_undo_actions', 1);
    }

    public function test_user_can_undo_custom_tone_deletion_from_its_success_toast(): void
    {
        $user = User::factory()->create();
        $tone = $user->customTones()->create([
            'name' => 'Friendly',
            'description' => 'Warm and conversational',
            'sample_content' => 'Hello there!',
        ]);

        $this->actingAs($user)->delete(route('tones.destroy', $tone));
        $undoUrl = session()->get('inertia.flash_data')['toast']['undoUrl'];
        $token = basename(parse_url($undoUrl, PHP_URL_PATH));

        $this->assertSoftDeleted('custom_tones', ['id' => $tone->id]);

        $this->post(route('tones.undo', $token))
            ->assertRedirect(route('tones.index'));

        $this->assertDatabaseHas('custom_tones', [
            'id' => $tone->id,
            'name' => 'Friendly',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseCount('custom_tone_undo_actions', 0);
    }

    public function test_custom_tone_delete_undo_expires_after_five_seconds(): void
    {
        $user = User::factory()->create();
        $tone = $user->customTones()->create([
            'name' => 'Friendly',
            'description' => 'Warm and conversational',
            'sample_content' => 'Hello there!',
        ]);

        $this->actingAs($user)->delete(route('tones.destroy', $tone));
        $undoUrl = session()->get('inertia.flash_data')['toast']['undoUrl'];
        $token = basename(parse_url($undoUrl, PHP_URL_PATH));

        Carbon::setTestNow(now()->addSeconds(6));

        try {
            $this->from(route('tones.index'))
                ->post(route('tones.undo', $token))
                ->assertRedirect(route('tones.index'))
                ->assertSessionHasErrors('undo');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSoftDeleted('custom_tones', ['id' => $tone->id]);
    }

    public function test_user_cannot_undo_another_users_custom_tone_deletion(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $tone = $owner->customTones()->create([
            'name' => 'Private',
            'description' => 'Private tone',
            'sample_content' => 'Private sample',
        ]);

        $this->actingAs($owner)->delete(route('tones.destroy', $tone));
        $undoUrl = session()->get('inertia.flash_data')['toast']['undoUrl'];
        $token = basename(parse_url($undoUrl, PHP_URL_PATH));

        $this->from(route('tones.index'))
            ->actingAs($anotherUser)
            ->post(route('tones.undo', $token))
            ->assertRedirect(route('tones.index'))
            ->assertSessionHasErrors('undo');

        $this->assertSoftDeleted('custom_tones', ['id' => $tone->id]);
    }

    public function test_user_cannot_view_update_or_delete_another_users_tone(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $tone = $owner->customTones()->create([
            'name' => 'Private',
            'description' => 'Private description',
            'sample_content' => 'Private sample',
        ]);

        $this->actingAs($anotherUser)
            ->get(route('tones.edit', $tone))
            ->assertNotFound();

        $this->put(route('tones.update', $tone), [
            'name' => 'Changed',
            'description' => 'Changed description',
            'sample_content' => 'Changed sample',
        ])->assertNotFound();

        $this->delete(route('tones.destroy', $tone))->assertNotFound();

        $this->assertDatabaseHas('custom_tones', [
            'id' => $tone->id,
            'name' => 'Private',
        ]);
    }

    public function test_tone_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('tones.store'), [])
            ->assertSessionHasErrors(['name', 'description', 'sample_content']);
    }
}
