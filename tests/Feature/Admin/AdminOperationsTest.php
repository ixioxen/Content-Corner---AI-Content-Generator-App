<?php

namespace Tests\Feature\Admin;

use App\Enums\CreditTransactionType;
use App\Enums\UserStatus;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_phase_five_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.overview'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Overview'));
        $this->get(route('admin.users.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Users'));
        $this->get(route('admin.transactions.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Transactions'));
        $this->get(route('admin.audit-logs.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/AuditLogs'));
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->patch(route('admin.users.status', $target), ['status' => UserStatus::Suspended->value])->assertForbidden();
    }

    public function test_admin_can_suspend_and_reactivate_users_with_audit_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.status', $user), [
            'status' => UserStatus::Suspended->value,
        ])->assertRedirect();

        $this->assertSame(UserStatus::Suspended, $user->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.suspended',
            'target_id' => $user->id,
        ]);

        $this->patch(route('admin.users.status', $user), [
            'status' => UserStatus::Active->value,
        ])->assertRedirect();

        $this->assertSame(UserStatus::Active, $user->fresh()->status);
        $this->assertSame(2, AdminAuditLog::query()->count());
    }

    public function test_admin_cannot_suspend_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.users.status', $admin), [
            'status' => UserStatus::Suspended->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(UserStatus::Active, $admin->fresh()->status);
        $this->assertDatabaseCount('admin_audit_logs', 0);
    }

    public function test_credit_adjustment_updates_balance_ledger_and_audit_atomically_and_is_idempotent(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['credits_balance' => 10]);
        $payload = [
            'amount' => 5,
            'idempotency_key' => '9f24d286-9f2d-4bdb-bbc9-ff3bb0e9c691',
            'reason' => 'Support correction',
        ];

        $this->actingAs($admin)->post(route('admin.users.credits', $user), $payload)->assertRedirect();
        $this->post(route('admin.users.credits', $user), $payload)->assertRedirect();

        $this->assertSame(15, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 5,
            'type' => CreditTransactionType::AdminAdjustment->value,
            'description' => 'Support correction',
            'created_by_admin_id' => $admin->id,
        ]);
        $this->assertDatabaseCount('admin_audit_logs', 1);
        $this->assertSame(5, AdminAuditLog::query()->firstOrFail()->metadata['amount']);
    }

    public function test_credit_adjustment_cannot_create_a_negative_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['credits_balance' => 2]);

        $this->actingAs($admin)->post(route('admin.users.credits', $user), [
            'amount' => -3,
            'idempotency_key' => '9f24d286-9f2d-4bdb-bbc9-ff3bb0e9c691',
            'reason' => 'Incorrect grant',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(2, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
        $this->assertDatabaseCount('admin_audit_logs', 0);
    }
}
