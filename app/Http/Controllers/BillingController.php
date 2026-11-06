<?php

namespace App\Http\Controllers;

use App\Models\CreditPackage;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __invoke(Request $request, StripeCheckoutService $stripe): Response
    {
        $user = $request->user();

        return Inertia::render('Billing/Index', [
            'creditsBalance' => $user->credits_balance,
            'checkoutAvailable' => $stripe->isConfigured(),
            'checkoutResult' => in_array($request->query('checkout'), ['complete', 'cancelled'], true)
                ? $request->query('checkout')
                : null,
            'packages' => CreditPackage::query()
                ->where('is_active', true)
                ->orderBy('price_amount')
                ->get(['id', 'name', 'credits_amount', 'price_amount', 'currency']),
            'purchases' => $user->creditPurchases()
                ->latest()
                ->limit(20)
                ->get(['id', 'package_name', 'credits_amount', 'price_amount', 'currency', 'status', 'created_at', 'stripe_checkout_session_id'])
                ->map(fn ($purchase) => [
                    'id' => $purchase->id,
                    'package_name' => $purchase->package_name,
                    'credits_amount' => $purchase->credits_amount,
                    'price_amount' => $purchase->price_amount,
                    'currency' => $purchase->currency,
                    'status' => $purchase->status,
                    'created_at' => $purchase->created_at,
                    'can_verify' => filled($purchase->stripe_checkout_session_id),
                ])
                ->values(),
            'transactions' => $user->creditTransactions()
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
