<?php

namespace Tests\Feature;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
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
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.description', 'Signup bonus')
            ->has('recentGenerations', 0));
    }
}
