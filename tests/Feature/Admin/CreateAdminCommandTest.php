<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin_without_signup_bonus(): void
    {
        $this->artisan('app:create-admin', [
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            '--password' => 'password',
        ])->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertSame(0, $admin->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_command_does_not_duplicate_existing_email(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin', [
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            '--password' => 'password',
        ])->assertFailed();

        $this->assertSame(1, User::query()->where('email', 'admin@example.com')->count());
        $this->assertFalse(User::query()->where('email', 'admin@example.com')->first()?->isAdmin());
    }

    public function test_non_admins_are_forbidden_by_admin_middleware(): void
    {
        Route::middleware(['web', 'auth', 'admin'])->get('/__admin-gate', fn () => 'ok');

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user)->get('/__admin-gate')->assertForbidden();
        $this->actingAs($admin)->get('/__admin-gate')->assertOk();
    }
}
