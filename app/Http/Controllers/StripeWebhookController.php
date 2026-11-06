<?php

namespace App\Http\Controllers;

use App\Models\CreditPurchase;
use App\Services\CreditPurchaseService;
use App\Services\CreditService;
use App\Services\StripeCurrency;
use App\Services\StripeWebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeWebhookSignature $signatures,
        CreditPurchaseService $purchases,
        CreditService $credits,
    ): JsonResponse {
        $payload = $request->getContent();
        $webhookSecret = config('services.stripe.webhook_secret');

        if (
            ! is_string($webhookSecret)
            || ! $signatures->isValid($payload, $request->header('Stripe-Signature'), $webhookSecret)
        ) {
            return response()->json(['message' => 'Invalid Stripe signature.'], 400);
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['message' => 'Invalid event payload.'], 400);
        }

        $type = data_get($event, 'type');
        $session = data_get($event, 'data.object');

        if (! is_string($type) || ! is_array($session)) {
            return response()->json(['message' => 'Invalid event payload.'], 400);
        }

        if (! in_array($type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
        ], true)) {
            return response()->json(['received' => true]);
        }

        $secretKey = config('services.stripe.secret');
        $expectedLiveMode = is_string($secretKey) && str_starts_with($secretKey, 'sk_live_');

        if (! is_bool(data_get($event, 'livemode')) || data_get($event, 'livemode') !== $expectedLiveMode) {
            Log::warning('Stripe webhook mode did not match the configured API key.');

            return response()->json(['received' => true]);
        }

        $purchaseId = data_get($session, 'client_reference_id');
        $sessionId = data_get($session, 'id');
        $metadataPurchaseId = data_get($session, 'metadata.purchase_id');
        $metadataUserId = data_get($session, 'metadata.user_id');

        if (
            ! is_string($purchaseId)
            || ! ctype_digit($purchaseId)
            || ! is_string($sessionId)
            || ! str_starts_with($sessionId, 'cs_')
            || $metadataPurchaseId !== $purchaseId
            || ! is_string($metadataUserId)
            || ! ctype_digit($metadataUserId)
        ) {
            Log::warning('Stripe webhook did not include valid purchase references.');

            return response()->json(['received' => true]);
        }

        $purchase = CreditPurchase::query()
            ->whereKey((int) $purchaseId)
            ->where('user_id', (int) $metadataUserId)
            ->first();

        if ($purchase === null) {
            Log::warning('Stripe webhook referenced an unknown credit purchase.');

            return response()->json(['received' => true]);
        }

        if (
            $purchase->stripe_checkout_session_id !== null
            && $purchase->stripe_checkout_session_id !== $sessionId
        ) {
            Log::warning('Stripe webhook Checkout Session did not match the purchase record.', [
                'purchase_id' => $purchase->id,
            ]);

            return response()->json(['received' => true]);
        }

        if (
            data_get($session, 'mode') !== 'payment'
            || (int) data_get($session, 'amount_total') !== StripeCurrency::toMinorUnits(
                $purchase->price_amount,
                strtoupper($purchase->currency),
            )
            || strtolower((string) data_get($session, 'currency')) !== strtolower($purchase->currency)
        ) {
            Log::warning('Stripe webhook payment details did not match the purchase snapshot.', [
                'purchase_id' => $purchase->id,
            ]);

            return response()->json(['received' => true]);
        }

        if (
            in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)
            && data_get($session, 'payment_status') === 'paid'
        ) {
            $paymentIntent = data_get($session, 'payment_intent');

            $purchases->completeVerifiedPayment(
                purchase: $purchase,
                credits: $credits,
                checkoutSessionId: $sessionId,
                paymentIntentId: is_string($paymentIntent) ? $paymentIntent : null,
                allowPreviouslyFailed: true,
            );
        } elseif (in_array($type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
            $purchases->markPaymentFailed($purchase, $sessionId);
        }

        return response()->json(['received' => true]);
    }
}
