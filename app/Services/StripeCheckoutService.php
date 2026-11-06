<?php

namespace App\Services;

use App\Exceptions\StripeCheckoutException;
use App\Models\CreditPurchase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeCheckoutService
{
    public function isConfigured(): bool
    {
        $publishableKey = config('services.stripe.key');
        $secretKey = config('services.stripe.secret');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (
            ! is_string($publishableKey)
            || ! is_string($secretKey)
            || ! is_string($webhookSecret)
            || ! preg_match('/^pk_(test|live)_/', $publishableKey)
            || ! preg_match('/^sk_(test|live)_/', $secretKey)
            || ! str_starts_with($webhookSecret, 'whsec_')
        ) {
            return false;
        }

        return str_starts_with($publishableKey, 'pk_live_')
            === str_starts_with($secretKey, 'sk_live_');
    }

    public function createSession(CreditPurchase $purchase): string
    {
        $secret = config('services.stripe.secret');

        if (! $this->isConfigured() || ! is_string($secret)) {
            throw new StripeCheckoutException;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($secret, '')
                ->withHeaders(['Idempotency-Key' => 'credit_purchase:'.$purchase->id.':checkout'])
                ->timeout(20)
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => route('billing.index', ['checkout' => 'complete']),
                    'cancel_url' => route('billing.index', ['checkout' => 'cancelled']),
                    'client_reference_id' => (string) $purchase->id,
                    'metadata[purchase_id]' => (string) $purchase->id,
                    'metadata[user_id]' => (string) $purchase->user_id,
                    'line_items[0][price_data][currency]' => strtolower($purchase->currency),
                    'line_items[0][price_data][unit_amount]' => StripeCurrency::toMinorUnits(
                        $purchase->price_amount,
                        strtoupper($purchase->currency),
                    ),
                    'line_items[0][price_data][product_data][name]' => $purchase->package_name,
                    'line_items[0][quantity]' => 1,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Stripe Checkout session request failed to connect.', [
                'purchase_id' => $purchase->id,
                'exception' => $exception::class,
            ]);

            throw new StripeCheckoutException;
        }

        if (! $response->successful()) {
            Log::warning('Stripe Checkout session request returned an unsuccessful status.', [
                'purchase_id' => $purchase->id,
                'status' => $response->status(),
                'stripe_error_type' => $response->json('error.type'),
                'stripe_error_code' => $response->json('error.code'),
            ]);

            throw new StripeCheckoutException;
        }

        $session = $response->json();
        $sessionId = data_get($session, 'id');
        $checkoutUrl = data_get($session, 'url');
        $expectedAmount = StripeCurrency::toMinorUnits($purchase->price_amount, strtoupper($purchase->currency));

        if (
            ! is_string($sessionId)
            || ! str_starts_with($sessionId, 'cs_')
            || ! is_string($checkoutUrl)
            || parse_url($checkoutUrl, PHP_URL_SCHEME) !== 'https'
            || parse_url($checkoutUrl, PHP_URL_HOST) !== 'checkout.stripe.com'
            || data_get($session, 'mode') !== 'payment'
            || data_get($session, 'client_reference_id') !== (string) $purchase->id
            || (int) data_get($session, 'amount_total') !== $expectedAmount
            || strtolower((string) data_get($session, 'currency')) !== strtolower($purchase->currency)
        ) {
            Log::warning('Stripe returned an invalid Checkout session response.', [
                'purchase_id' => $purchase->id,
            ]);

            throw new StripeCheckoutException;
        }

        $purchase->update(['stripe_checkout_session_id' => $sessionId]);

        return $checkoutUrl;
    }

    public function verifyAndComplete(
        CreditPurchase $purchase,
        CreditPurchaseService $purchases,
        CreditService $credits,
    ): bool {
        $secret = config('services.stripe.secret');
        $sessionId = $purchase->stripe_checkout_session_id;

        if (! $this->isConfigured() || ! is_string($secret) || ! is_string($sessionId)) {
            throw new StripeCheckoutException;
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->acceptJson()
                ->timeout(20)
                ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($sessionId));
        } catch (ConnectionException $exception) {
            Log::warning('Stripe Checkout session verification failed to connect.', [
                'purchase_id' => $purchase->id,
                'exception' => $exception::class,
            ]);

            throw new StripeCheckoutException;
        }

        if (! $response->successful()) {
            Log::warning('Stripe Checkout session verification returned an unsuccessful status.', [
                'purchase_id' => $purchase->id,
                'status' => $response->status(),
                'stripe_error_type' => $response->json('error.type'),
                'stripe_error_code' => $response->json('error.code'),
            ]);

            throw new StripeCheckoutException;
        }

        $session = $response->json();
        $expectedAmount = StripeCurrency::toMinorUnits($purchase->price_amount, strtoupper($purchase->currency));
        $expectedLiveMode = str_starts_with($secret, 'sk_live_');

        if (
            data_get($session, 'id') !== $sessionId
            || data_get($session, 'mode') !== 'payment'
            || data_get($session, 'client_reference_id') !== (string) $purchase->id
            || data_get($session, 'metadata.purchase_id') !== (string) $purchase->id
            || data_get($session, 'metadata.user_id') !== (string) $purchase->user_id
            || (int) data_get($session, 'amount_total') !== $expectedAmount
            || strtolower((string) data_get($session, 'currency')) !== strtolower($purchase->currency)
            || data_get($session, 'livemode') !== $expectedLiveMode
        ) {
            Log::warning('Stripe Checkout session verification did not match the purchase record.', [
                'purchase_id' => $purchase->id,
            ]);

            throw new StripeCheckoutException;
        }

        if (data_get($session, 'payment_status') !== 'paid' || data_get($session, 'status') !== 'complete') {
            return false;
        }

        $paymentIntent = data_get($session, 'payment_intent');
        $purchases->completeVerifiedPayment(
            purchase: $purchase,
            credits: $credits,
            checkoutSessionId: $sessionId,
            paymentIntentId: is_string($paymentIntent) ? $paymentIntent : null,
            allowPreviouslyFailed: true,
        );

        return true;
    }
}
