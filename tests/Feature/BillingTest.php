<?php

namespace Tests\Feature;

use App\Enums\CreditTransactionType;
use App\Models\AdminAuditLog;
use App\Models\CreditPackage;
use App\Models\CreditPurchase;
use App\Models\User;
use App\Services\CreditPurchaseService;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_lists_active_packages_and_only_the_users_own_activity(): void
    {
        $user = User::factory()->create(['credits_balance' => 27]);
        $otherUser = User::factory()->create();
        $activePackage = $this->createPackage();
        $this->createPackage(['name' => 'Hidden', 'is_active' => false]);
        $userPurchase = app(CreditPurchaseService::class)->createPending($user, $activePackage->id);
        $otherPurchase = app(CreditPurchaseService::class)->createPending($otherUser, $activePackage->id);
        $credits = app(CreditService::class);
        $credits->apply($user, 1, CreditTransactionType::SignupBonus, 'billing-test:user', 'User-only activity');
        $credits->apply($otherUser, 1, CreditTransactionType::SignupBonus, 'billing-test:other', 'Other-user activity');

        $this->actingAs($user->fresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Billing/Index')
                ->where('creditsBalance', 28)
                ->has('packages', 1)
                ->has('purchases', 1)
                ->where('purchases.0.id', $userPurchase->id)
                ->where('purchases.0.package_name', $activePackage->name)
                ->has('transactions.data', 1)
                ->where('transactions.data.0.description', 'User-only activity'));

        $this->assertNotSame($userPurchase->id, $otherPurchase->id);
    }

    public function test_package_snapshot_is_saved_and_inactive_packages_cannot_be_purchased(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();

        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $package->update([
            'name' => 'Updated package',
            'credits_amount' => 200,
            'price_amount' => '19.99',
        ]);

        $this->assertSame('Starter', $purchase->fresh()->package_name);
        $this->assertSame(100, $purchase->fresh()->credits_amount);
        $this->assertSame('9.99', $purchase->fresh()->price_amount);
        $this->assertSame(20, $user->fresh()->credits_balance);

        $package->update(['is_active' => false]);
        try {
            app(CreditPurchaseService::class)->createPending($user, $package->id);
            $this->fail('Inactive packages must not create purchases.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('credit_purchases', 1);
        }
    }

    public function test_verified_payment_grants_package_credits_once(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage(['credits_amount' => 80]);
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $service = app(CreditPurchaseService::class);
        $credits = app(CreditService::class);

        $service->completeVerifiedPayment($purchase, $credits, 'cs_test_123', 'pi_test_123');
        $service->completeVerifiedPayment($purchase->fresh(), $credits, 'cs_test_123', 'pi_test_123');

        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame(100, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 80,
            'type' => CreditTransactionType::Purchase->value,
            'reference_type' => CreditPurchase::class,
            'reference_id' => $purchase->id,
            'idempotency_key' => 'credit_purchase:'.$purchase->id.':grant',
        ]);
    }

    public function test_failed_payment_does_not_grant_credits_and_cannot_be_completed_later(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $service = app(CreditPurchaseService::class);
        $credits = app(CreditService::class);

        $service->markPaymentFailed($purchase);
        $service->markPaymentFailed($purchase->fresh());
        $service->completeVerifiedPayment($purchase->fresh(), $credits, 'cs_test_123', 'pi_test_123');

        $this->assertSame('failed', $purchase->fresh()->status);
        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_admin_can_manage_packages_and_changes_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $package = $this->createPackage();

        $this->actingAs($admin)
            ->get(route('admin.credit-packages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/CreditPackages')
                ->has('packages', 1));

        $this->post(route('admin.credit-packages.store'), [
            'name' => 'Team',
            'credits_amount' => 500,
            'price_amount' => '39.99',
            'currency' => 'eur',
            'is_active' => true,
        ])->assertRedirect();

        $this->patch(route('admin.credit-packages.update', $package), [
            'name' => 'Starter Plus',
            'credits_amount' => 150,
            'price_amount' => '12.50',
            'currency' => 'usd',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('credit_packages', [
            'name' => 'Team',
            'currency' => 'EUR',
        ]);
        $this->assertDatabaseHas('credit_packages', [
            'id' => $package->id,
            'name' => 'Starter Plus',
            'currency' => 'USD',
            'is_active' => false,
        ]);
        $this->assertSame(2, AdminAuditLog::query()->count());
    }

    public function test_non_admin_cannot_manage_credit_packages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.credit-packages.index'))
            ->assertForbidden();

        $this->post(route('admin.credit-packages.store'), [
            'name' => 'Unauthorized',
            'credits_amount' => 100,
            'price_amount' => '9.99',
            'currency' => 'USD',
            'is_active' => true,
        ])->assertForbidden();

        $this->assertDatabaseCount('credit_packages', 0);
        $this->assertDatabaseCount('admin_audit_logs', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPackage(array $overrides = []): CreditPackage
    {
        return CreditPackage::query()->create([
            'name' => 'Starter',
            'credits_amount' => 100,
            'price_amount' => '9.99',
            'currency' => 'USD',
            'is_active' => true,
            ...$overrides,
        ]);
    }
}
