<?php

namespace Tests\Feature;

use App\Models\BrandProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_brand_profile_with_content_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('brands.store'), $this->profileData());

        $profile = BrandProfile::query()->firstOrFail();

        $response->assertRedirect(route('brands.index'));
        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame('Cloud Coffee', $profile->brand_name);
        $this->assertSame(['fresh', 'welcoming'], $profile->preferred_words);
        $this->assertSame(['cheap'], $profile->avoid_words);
    }

    public function test_user_can_update_and_delete_their_brand_profile(): void
    {
        $user = User::factory()->create();
        $profile = $user->brandProfiles()->create($this->profileData());

        $this->actingAs($user)
            ->put(route('brands.update', $profile), [
                ...$this->profileData(),
                'brand_name' => 'Updated Coffee',
            ])
            ->assertRedirect(route('brands.index'));

        $this->assertDatabaseHas('brand_profiles', [
            'id' => $profile->id,
            'brand_name' => 'Updated Coffee',
        ]);

        $this->delete(route('brands.destroy', $profile))
            ->assertRedirect(route('brands.index'));

        $this->assertDatabaseMissing('brand_profiles', ['id' => $profile->id]);
    }

    public function test_user_cannot_edit_or_delete_another_users_brand_profile(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $profile = $owner->brandProfiles()->create($this->profileData());

        $this->actingAs($anotherUser)
            ->get(route('brands.edit', $profile))
            ->assertNotFound();

        $this->put(route('brands.update', $profile), $this->profileData())
            ->assertNotFound();

        $this->delete(route('brands.destroy', $profile))
            ->assertNotFound();

        $this->assertDatabaseHas('brand_profiles', [
            'id' => $profile->id,
            'brand_name' => 'Cloud Coffee',
        ]);
    }

    public function test_user_can_create_at_most_the_configured_number_of_profiles(): void
    {
        $user = User::factory()->create();

        foreach (range(1, config('brand_profiles.max_per_user')) as $number) {
            $user->brandProfiles()->create([
                ...$this->profileData(),
                'brand_name' => 'Brand '.$number,
            ]);
        }

        $this->actingAs($user)
            ->post(route('brands.store'), $this->profileData())
            ->assertSessionHasErrors('limit');

        $this->assertSame(config('brand_profiles.max_per_user'), $user->brandProfiles()->count());
    }

    public function test_brand_profile_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('brands.store'), [
                ...$this->profileData(),
                'brand_name' => '',
                'emoji_usage' => 'always',
                'preferred_words' => ['repeat', 'REPEAT'],
            ])
            ->assertSessionHasErrors(['brand_name', 'emoji_usage', 'preferred_words.1']);
    }

    public function test_brand_profile_list_only_shows_the_authenticated_users_profiles(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->brandProfiles()->create($this->profileData());
        $otherUser->brandProfiles()->create([
            ...$this->profileData(),
            'brand_name' => 'Private brand',
        ]);

        $this->actingAs($user)
            ->get(route('brands.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('BrandProfiles/Index')
                ->has('profiles', 1)
                ->where('profiles.0.brand_name', 'Cloud Coffee')
                ->where('profileLimit', config('brand_profiles.max_per_user'))
                ->where('canCreate', true));
    }

    /**
     * @return array<string, mixed>
     */
    private function profileData(): array
    {
        return [
            'brand_name' => 'Cloud Coffee',
            'industry' => 'Food and beverage',
            'target_audience' => 'Coffee lovers',
            'personality' => 'Warm and welcoming',
            'writing_style' => 'Short, friendly sentences',
            'preferred_words' => ['fresh', 'welcoming'],
            'avoid_words' => ['cheap'],
            'emoji_usage' => 'minimal',
            'language' => 'English',
            'main_platform' => 'Instagram',
            'pain_points' => 'Finding quality coffee nearby',
            'additional_instructions' => 'Mention locally roasted beans.',
        ];
    }
}
