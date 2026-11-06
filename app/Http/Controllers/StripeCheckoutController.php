<?php

namespace App\Http\Controllers;

use App\Exceptions\StripeCheckoutException;
use App\Models\User;
use App\Services\CreditPurchaseService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class StripeCheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        CreditPurchaseService $purchases,
        StripeCheckoutService $stripe,
    ): Response {
        $data = $request->validate([
            'package_id' => ['required', 'integer'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if (! $stripe->isConfigured()) {
            throw ValidationException::withMessages([
                'checkout' => 'Stripe checkout is not configured. Contact support.',
            ]);
        }

        $purchase = $purchases->createPending($user, (int) $data['package_id']);

        try {
            $checkoutUrl = $stripe->createSession($purchase);
        } catch (StripeCheckoutException $exception) {
            $purchases->markPaymentFailed($purchase);
            Log::warning('Credit purchase checkout could not be started.', [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
            ]);

            throw ValidationException::withMessages([
                'checkout' => $exception->userMessage(),
            ]);
        }

        return Inertia::location($checkoutUrl);
    }
}
