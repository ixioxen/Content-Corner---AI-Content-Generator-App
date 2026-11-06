<?php

namespace Tests\Feature;

use App\Enums\CreditTransactionType;
use App\Models\CreditPackage;
use App\Models\CreditPurchase;
use App\Models\User;
use App\Services\CreditPurchaseService;
use App\Services\StripeCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_signing_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.key' => 'pk_test_example',
            'services.stripe.secret' => 'sk_test_example',
            'services.stripe.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
    }

    public function test_billing_page_enables_checkout_only_when_stripe_is_configured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Billing/Index')
                ->where('checkoutAvailable', true));

        config(['services.stripe.webhook_secret' => null]);

        $this->get(route('billing.index'))
            ->assertInertia(fn ($page) => $page->where('checkoutAvailable', false));
    }

    public function test_checkout_uses_server_package_snapshot_and_redirects_to_stripe(): void
    {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_checkout_123',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_checkout_123',
                'mode' => 'payment',
                'client_reference_id' => '1',
                'amount_total' => 999,
                'currency' => 'usd',
            ]),
        ]);
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Inertia' => 'true'])
            ->post(route('billing.purchases.store'), ['package_id' => $package->id]);

        $response->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_checkout_123');

        $purchase = CreditPurchase::query()->firstOrFail();
        $this->assertSame('pending', $purchase->status);
        $this->assertSame('cs_test_checkout_123', $purchase->stripe_checkout_session_id);
        $this->assertSame(20, $user->fresh()->credits_balance);

        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), '/v1/checkout/sessions')
            && $request->hasHeader('Idempotency-Key', 'credit_purchase:'.$purchase->id.':checkout'));
        $request = Http::recorded()->first()[0];
        $this->assertSame('payment', $request['mode']);
        $this->assertSame((string) $purchase->id, $request['client_reference_id']);
        $this->assertSame(999, $request['line_items[0][price_data][unit_amount]']);
        $this->assertSame('usd', $request['line_items[0][price_data][currency]']);
    }

    public function test_checkout_failure_records_failed_purchase_and_grants_no_credits(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['type' => 'api_error']], 500)]);
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();

        $this->actingAs($user)
            ->from(route('billing.index'))
            ->post(route('billing.purchases.store'), ['package_id' => $package->id])
            ->assertRedirect(route('billing.index'))
            ->assertSessionHasErrors('checkout');

        $this->assertSame('failed', CreditPurchase::query()->firstOrFail()->status);
        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_signed_success_webhook_grants_credits_once_even_when_retried(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage(['credits_amount' => 80]);
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $event = $this->event('checkout.session.completed', $this->checkoutSessionData($purchase));

        $this->sendSignedEvent($event)->assertOk()->assertExactJson(['received' => true]);
        $this->sendSignedEvent($event)->assertOk()->assertExactJson(['received' => true]);

        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame('cs_test_checkout_123', $purchase->fresh()->stripe_checkout_session_id);
        $this->assertSame('pi_test_payment_123', $purchase->fresh()->stripe_payment_intent_id);
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

    public function test_invalid_webhook_signature_cannot_grant_credits(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $payload = json_encode($this->event('checkout.session.completed', $this->checkoutSessionData($purchase)), JSON_THROW_ON_ERROR);

        $this->call('POST', route('stripe.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=invalid',
        ], $payload)->assertBadRequest();

        $this->assertSame('pending', $purchase->fresh()->status);
        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_expired_checkout_is_failed_without_granting_credits(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $session = $this->checkoutSessionData($purchase);
        $session['payment_status'] = 'unpaid';

        $this->sendSignedEvent($this->event('checkout.session.expired', $session))
            ->assertOk();

        $this->assertSame('failed', $purchase->fresh()->status);
        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_verified_late_success_after_failed_event_grants_credits_once(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage(['credits_amount' => 80]);
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $session = $this->checkoutSessionData($purchase);
        $session['payment_status'] = 'unpaid';

        $this->sendSignedEvent($this->event('checkout.session.async_payment_failed', $session))->assertOk();
        $this->sendSignedEvent($this->event('checkout.session.async_payment_succeeded', $this->checkoutSessionData($purchase)))
            ->assertOk();

        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame(100, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 1);
    }

    public function test_stripe_amount_conversion_handles_zero_and_three_decimal_currencies(): void
    {
        $this->assertSame(100, StripeCurrency::toMinorUnits('100.00', 'JPY'));
        $this->assertSame(1250, StripeCurrency::toMinorUnits('12.50', 'USD'));
        $this->assertSame(12500, StripeCurrency::toMinorUnits('12.50', 'KWD'));

        $this->expectException(\InvalidArgumentException::class);
        StripeCurrency::toMinorUnits('100.50', 'JPY');
    }

    public function test_checkout_is_disabled_when_publishable_and_secret_key_modes_do_not_match(): void
    {
        config([
            'services.stripe.key' => 'pk_test_example',
            'services.stripe.secret' => 'sk_live_example',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertInertia(fn ($page) => $page->where('checkoutAvailable', false));
    }

    public function test_user_can_verify_a_completed_stripe_payment_without_webhook_delivery(): void
    {
        $user = User::factory()->create(['credits_balance' => 34]);
        $package = $this->createPackage(['credits_amount' => 20, 'price_amount' => '6.00']);
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $purchase->update(['stripe_checkout_session_id' => 'cs_test_reconcile_123']);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions/cs_test_reconcile_123' => Http::response([
                'id' => 'cs_test_reconcile_123',
                'mode' => 'payment',
                'status' => 'complete',
                'payment_status' => 'paid',
                'amount_total' => 600,
                'currency' => 'usd',
                'livemode' => false,
                'client_reference_id' => (string) $purchase->id,
                'payment_intent' => 'pi_test_reconcile_123',
                'metadata' => [
                    'purchase_id' => (string) $purchase->id,
                    'user_id' => (string) $user->id,
                ],
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('billing.purchases.verify', $purchase))
            ->assertRedirect();

        $this->assertSame('paid', $purchase->fresh()->status);
        $this->assertSame(54, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 1);
    }

    public function test_user_cannot_verify_another_users_purchase(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $package = $this->createPackage();
        $purchase = app(CreditPurchaseService::class)->createPending($owner, $package->id);
        Http::fake();

        $this->actingAs($otherUser)
            ->post(route('billing.purchases.verify', $purchase))
            ->assertNotFound();

        Http::assertNothingSent();
        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_verify_does_not_grant_credits_when_stripe_reports_unpaid(): void
    {
        $user = User::factory()->create(['credits_balance' => 20]);
        $package = $this->createPackage();
        $purchase = app(CreditPurchaseService::class)->createPending($user, $package->id);
        $purchase->update(['stripe_checkout_session_id' => 'cs_test_unpaid_123']);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions/cs_test_unpaid_123' => Http::response([
                'id' => 'cs_test_unpaid_123',
                'mode' => 'payment',
                'status' => 'open',
                'payment_status' => 'unpaid',
                'amount_total' => 999,
                'currency' => 'usd',
                'livemode' => false,
                'client_reference_id' => (string) $purchase->id,
                'metadata' => [
                    'purchase_id' => (string) $purchase->id,
                    'user_id' => (string) $user->id,
                ],
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('billing.purchases.verify', $purchase))
            ->assertRedirect();

        $this->assertSame('pending', $purchase->fresh()->status);
        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
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

    /**
     * @return array<string, mixed>
     */
    private function checkoutSessionData(CreditPurchase $purchase): array
    {
        return [
            'id' => 'cs_test_checkout_123',
            'mode' => 'payment',
            'client_reference_id' => (string) $purchase->id,
            'payment_status' => 'paid',
            'amount_total' => 999,
            'currency' => 'usd',
            'payment_intent' => 'pi_test_payment_123',
            'metadata' => [
                'purchase_id' => (string) $purchase->id,
                'user_id' => (string) $purchase->user_id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function event(string $type, array $session): array
    {
        return [
            'id' => 'evt_test_checkout_123',
            'type' => $type,
            'livemode' => false,
            'data' => ['object' => $session],
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function sendSignedEvent(array $event)
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, self::WEBHOOK_SECRET);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        ], $payload);
    }
}
