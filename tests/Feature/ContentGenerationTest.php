<?php

namespace Tests\Feature;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\GeneratedContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContentGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
        ]);
    }

    public function test_successful_generation_saves_content_and_charges_one_credit(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'Generated launch post.']],
                    ],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['credits_balance' => 3]);
        $brand = $user->brandProfiles()->create($this->brandData());
        $tone = $user->customTones()->create([
            'name' => 'Friendly',
            'description' => 'Warm and conversational',
            'sample_content' => 'Thanks for stopping by!',
        ]);

        $response = $this->actingAs($user)->post(route('generations.store'), $this->generationData([
            'brand_profile_id' => $brand->id,
            'custom_tone_id' => $tone->id,
        ]));

        $generation = GeneratedContent::query()->firstOrFail();

        $response->assertRedirect(route('generations.show', $generation));
        $this->assertSame('completed', $generation->status);
        $this->assertSame('Generated launch post.', $generation->generated_text);
        $this->assertSame('Cloud Coffee', $generation->brand_profile_snapshot['brand_name']);
        $this->assertSame('Friendly', $generation->custom_tone_snapshot['name']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'credits_balance' => 2]);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => -1,
            'type' => CreditTransactionType::Generation->value,
            'reference_type' => GeneratedContent::class,
            'reference_id' => $generation->id,
        ]);

        Http::assertSent(function (ClientRequest $request): bool {
            return str_contains($request->url(), '/models/gemini-test-model:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && str_contains($request['contents'][0]['parts'][0]['text'], 'Cloud Coffee')
                && str_contains($request['contents'][0]['parts'][0]['text'], 'Thanks for stopping by!');
        });
    }

    public function test_confirmed_gemini_failure_refunds_exactly_once(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Provider failed']], 503)]);
        $user = User::factory()->create(['credits_balance' => 2]);
        $data = $this->generationData();

        $this->actingAs($user)
            ->from(route('generations.create'))
            ->post(route('generations.store'), $data)
            ->assertRedirect(route('generations.create'))
            ->assertSessionHasErrors('generation');

        $generation = GeneratedContent::query()->firstOrFail();
        $this->assertSame('failed', $generation->status);
        $this->assertSame(2, $user->fresh()->credits_balance);
        $this->assertSame(2, CreditTransaction::query()->count());
        $this->assertDatabaseHas('credit_transactions', [
            'reference_id' => $generation->id,
            'amount' => -1,
            'type' => CreditTransactionType::Generation->value,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'reference_id' => $generation->id,
            'amount' => 1,
            'type' => CreditTransactionType::Refund->value,
        ]);

        $this->from(route('generations.create'))
            ->post(route('generations.store'), $data)
            ->assertSessionHasErrors('generation');

        $this->assertSame(2, CreditTransaction::query()->count());
        Http::assertSentCount(1);
    }

    public function test_gemini_connection_timeout_refunds_without_retrying(): void
    {
        $attempts = 0;
        Http::fake([
            '*' => function () use (&$attempts) {
                $attempts++;

                throw new ConnectionException('Connection timed out');
            },
        ]);
        $user = User::factory()->create(['credits_balance' => 2]);

        $this->actingAs($user)
            ->from(route('generations.create'))
            ->post(route('generations.store'), $this->generationData())
            ->assertRedirect(route('generations.create'))
            ->assertSessionHasErrors('generation');

        $generation = GeneratedContent::query()->firstOrFail();
        $this->assertSame('failed', $generation->status);
        $this->assertSame(2, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 2);
        $this->assertSame(1, $attempts);
    }

    public function test_zero_credit_request_is_rejected_before_gemini_is_called(): void
    {
        Http::fake();
        $user = User::factory()->create(['credits_balance' => 0]);

        $this->actingAs($user)
            ->from(route('generations.create'))
            ->post(route('generations.store'), $this->generationData())
            ->assertSessionHasErrors('credits');

        $this->assertDatabaseCount('generated_contents', 0);
        $this->assertDatabaseCount('credit_transactions', 0);
        Http::assertNothingSent();
    }

    public function test_distinct_generation_requests_cannot_spend_the_same_last_credit(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Generated once.']]]]],
        ])]);
        $user = User::factory()->create(['credits_balance' => 1]);
        $this->actingAs($user);

        $this->post(route('generations.store'), $this->generationData())
            ->assertRedirect();

        $this->from(route('generations.create'))
            ->post(route('generations.store'), $this->generationData())
            ->assertSessionHasErrors('credits');

        $this->assertSame(0, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('generated_contents', 1);
        $this->assertDatabaseCount('credit_transactions', 1);
        Http::assertSentCount(1);
    }

    public function test_unconfigured_gemini_is_rejected_without_reserving_a_credit(): void
    {
        config(['services.gemini.api_key' => null]);
        Http::fake();
        $user = User::factory()->create(['credits_balance' => 2]);

        $this->actingAs($user)
            ->from(route('generations.create'))
            ->post(route('generations.store'), $this->generationData())
            ->assertSessionHasErrors('generation');

        $this->assertSame(2, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('generated_contents', 0);
        Http::assertNothingSent();
    }

    public function test_duplicate_request_uuid_returns_existing_result_without_double_charge_or_call(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Only once.']]]]],
        ])]);
        $user = User::factory()->create(['credits_balance' => 2]);
        $data = $this->generationData();

        $firstResponse = $this->actingAs($user)->post(route('generations.store'), $data);
        $generation = GeneratedContent::query()->firstOrFail();

        $firstResponse->assertRedirect(route('generations.show', $generation));
        $this->post(route('generations.store'), [...$data, 'brief' => 'A different brief that must not run'])
            ->assertRedirect(route('generations.show', $generation));

        $this->assertDatabaseCount('generated_contents', 1);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertSame(1, $user->fresh()->credits_balance);
        Http::assertSentCount(1);
    }

    public function test_only_users_own_brand_profiles_and_tones_can_be_selected(): void
    {
        Http::fake();
        $user = User::factory()->create(['credits_balance' => 2]);
        $otherUser = User::factory()->create();
        $foreignBrand = $otherUser->brandProfiles()->create($this->brandData());
        $foreignTone = $otherUser->customTones()->create([
            'name' => 'Private',
            'description' => 'Private tone',
            'sample_content' => 'Private sample',
        ]);

        $this->actingAs($user)
            ->from(route('generations.create'))
            ->post(route('generations.store'), $this->generationData([
                'brand_profile_id' => $foreignBrand->id,
                'custom_tone_id' => $foreignTone->id,
            ]))
            ->assertSessionHasErrors(['brand_profile_id', 'custom_tone_id']);

        $this->assertSame(2, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('generated_contents', 0);
        Http::assertNothingSent();
    }

    public function test_request_fields_are_validated_before_credit_reservation(): void
    {
        Http::fake();
        $user = User::factory()->create(['credits_balance' => 2]);

        $this->actingAs($user)
            ->from(route('generations.create'))
            ->post(route('generations.store'), [
                'request_uuid' => 'not-a-uuid',
                'content_type' => 'unknown',
                'brief' => 'tiny',
            ])
            ->assertSessionHasErrors(['request_uuid', 'content_type', 'brief']);

        $this->assertSame(2, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('generated_contents', 0);
        Http::assertNothingSent();
    }

    public function test_users_cannot_view_or_delete_another_users_generated_content(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $generation = $owner->generatedContents()->create([
            ...$this->generationData(),
            'status' => 'completed',
            'generated_text' => 'Private result',
        ]);

        $this->actingAs($anotherUser)
            ->get(route('generations.show', $generation))
            ->assertNotFound();

        $this->delete(route('generations.destroy', $generation))
            ->assertNotFound();

        $this->assertDatabaseHas('generated_contents', [
            'id' => $generation->id,
            'generated_text' => 'Private result',
        ]);
    }

    public function test_generation_history_contains_only_the_authenticated_users_completed_items(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->generatedContents()->create([
            ...$this->generationData(),
            'status' => 'completed',
            'generated_text' => 'My result',
        ]);
        $user->generatedContents()->create([
            ...$this->generationData(['request_uuid' => (string) Str::uuid()]),
            'status' => 'failed',
        ]);
        $otherUser->generatedContents()->create([
            ...$this->generationData(),
            'status' => 'completed',
            'generated_text' => 'Other result',
        ]);

        $this->actingAs($user)
            ->get(route('generations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Generations/Index')
                ->has('generations.data', 1)
                ->where('generations.data.0.generated_text', 'My result'));
    }

    public function test_create_page_returns_only_the_users_brand_profiles_and_tones(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->brandProfiles()->create($this->brandData());
        $otherUser->brandProfiles()->create($this->brandData(['brand_name' => 'Other brand']));
        $user->customTones()->create([
            'name' => 'Friendly',
            'description' => 'Warm',
            'sample_content' => 'Hello!',
        ]);
        $otherUser->customTones()->create([
            'name' => 'Private',
            'description' => 'Private',
            'sample_content' => 'Private',
        ]);

        $this->actingAs($user)
            ->get(route('generations.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Generations/Create')
                ->where('providerConfigured', true)
                ->has('brandProfiles', 1)
                ->where('brandProfiles.0.brand_name', 'Cloud Coffee')
                ->has('tones', 1)
                ->where('tones.0.name', 'Friendly'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function generationData(array $overrides = []): array
    {
        return [
            'request_uuid' => (string) Str::uuid(),
            'content_type' => 'social_post',
            'brief' => 'Write a launch post for our new coffee.',
            'brand_profile_id' => null,
            'custom_tone_id' => null,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function brandData(array $overrides = []): array
    {
        return [
            'brand_name' => 'Cloud Coffee',
            'industry' => 'Food and beverage',
            'target_audience' => 'Coffee lovers',
            'personality' => 'Warm and welcoming',
            'writing_style' => 'Short, friendly sentences',
            'preferred_words' => ['fresh'],
            'avoid_words' => ['cheap'],
            'emoji_usage' => 'minimal',
            'language' => 'English',
            'main_platform' => 'Instagram',
            'pain_points' => 'Finding quality coffee nearby',
            'additional_instructions' => 'Mention locally roasted beans.',
            ...$overrides,
        ];
    }
}
