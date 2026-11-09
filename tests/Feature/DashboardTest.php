<?php

namespace Tests\Feature;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\GeneratedContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_shows_the_live_balance_and_only_the_users_recent_transactions(): void
    {
        $user = User::factory()->create(['credits_balance' => 17]);
        $otherUser = User::factory()->create(['credits_balance' => 99]);

        CreditTransaction::query()->create([
            'user_id' => $user->id,
            'amount' => 20,
            'type' => CreditTransactionType::SignupBonus,
            'idempotency_key' => 'signup_bonus:'.$user->id,
            'description' => 'Signup bonus',
        ]);

        CreditTransaction::query()->create([
            'user_id' => $otherUser->id,
            'amount' => 20,
            'type' => CreditTransactionType::SignupBonus,
            'idempotency_key' => 'signup_bonus:'.$otherUser->id,
            'description' => 'Other user bonus',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('creditsBalance', 17)
            ->where('generationsCount', 0)
            ->where('customTonesCount', 0)
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.description', 'Signup bonus')
            ->has('recentGenerations', 0));
    }

    public function test_dashboard_counts_completed_generations_and_custom_tones(): void
    {
        $user = User::factory()->create();
        $user->customTones()->create([
            'name' => 'Friendly',
            'description' => 'Warm and conversational.',
            'sample_content' => 'Thanks for being here.',
        ]);

        GeneratedContent::query()->create([
            'user_id' => $user->id,
            'request_uuid' => '0fa7f8d4-f551-4335-81e2-dba138b8d4da',
            'content_type' => 'social_post',
            'brief' => 'Write a product announcement.',
            'generated_text' => 'A new product is here.',
            'status' => 'completed',
        ]);

        GeneratedContent::query()->create([
            'user_id' => $user->id,
            'request_uuid' => '0fa7f8d4-f551-4335-81e2-dba138b8d4db',
            'content_type' => 'social_post',
            'brief' => 'Write another product announcement.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('generationsCount', 1)
            ->where('customTonesCount', 1)
            ->has('recentGenerations', 1));
    }
}
