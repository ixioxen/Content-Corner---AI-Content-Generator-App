<?php

namespace App\Http\Controllers;

use App\Exceptions\StripeCheckoutException;
use App\Models\CreditPurchase;
use App\Models\User;
use App\Services\CreditPurchaseService;
use App\Services\CreditService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VerifyCreditPurchaseController extends Controller
{
    public function __invoke(
        Request $request,
        CreditPurchase $purchase,
        StripeCheckoutService $stripe,
        CreditPurchaseService $purchases,
        CreditService $credits,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User && $purchase->user_id === $user->id, 404);

        if ($purchase->status === 'paid') {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'This purchase is already confirmed.']);

            return back();
        }

        try {
            $paid = $stripe->verifyAndComplete($purchase, $purchases, $credits);
        } catch (StripeCheckoutException $exception) {
            Log::warning('User requested Stripe purchase verification but Stripe could not verify it.', [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
            ]);

            throw ValidationException::withMessages([
                'purchase' => 'Stripe could not verify this purchase right now. Please try again shortly.',
            ]);
        }

        Inertia::flash('toast', $paid
            ? ['type' => 'success', 'message' => 'Payment confirmed. Your credits have been added.']
            : ['type' => 'info', 'message' => 'Stripe has not confirmed this payment yet. Your balance is unchanged.']);

        return back();
    }
}
