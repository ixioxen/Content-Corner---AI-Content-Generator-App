<?php

namespace Tests\Feature\Auth;

use App\Enums\CreditTransactionType;
use App\Enums\UserRole;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationCreditsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_grants_signup_bonus_once_with_ledger_row(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(20, $user->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 20,
            'type' => CreditTransactionType::SignupBonus->value,
            'idempotency_key' => 'signup_bonus:'.$user->id,
        ]);
    }

    public function test_public_registration_cannot_set_admin_role(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'credits_balance' => 999,
        ]);

        $user = User::query()->where('email', 'hacker@example.com')->firstOrFail();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(20, $user->credits_balance);
        $this->assertFalse($user->isAdmin());
    }

    public function test_signup_bonus_is_idempotent(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        app(CreditService::class)->grantSignupBonus($user);

        $user->refresh();

        $this->assertSame(20, $user->credits_balance);
        $this->assertSame(1, CreditTransaction::query()->where('user_id', $user->id)->count());
    }
}
