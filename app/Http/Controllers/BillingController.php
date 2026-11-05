<?php

namespace App\Http\Controllers;

use App\Models\CreditPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Billing/Index', [
            'creditsBalance' => $user->credits_balance,
            'packages' => CreditPackage::query()
                ->where('is_active', true)
                ->orderBy('price_amount')
                ->get(['id', 'name', 'credits_amount', 'price_amount', 'currency']),
            'purchases' => $user->creditPurchases()
                ->latest()
                ->limit(20)
                ->get(['id', 'package_name', 'credits_amount', 'price_amount', 'currency', 'status', 'created_at']),
            'transactions' => $user->creditTransactions()
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
