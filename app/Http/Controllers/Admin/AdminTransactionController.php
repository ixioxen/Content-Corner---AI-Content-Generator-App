<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CreditTransactionType;
use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTransactionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $transactions = CreditTransaction::query()
            ->with(['user:id,name,email', 'createdByAdmin:id,name,email'])
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $generationTransactions = CreditTransaction::query()
            ->where('type', CreditTransactionType::Generation->value)
            ->where('amount', '<', 0);

        return Inertia::render('Admin/Transactions', [
            'transactions' => $transactions,
            'usage' => [
                'generationCount' => (clone $generationTransactions)->count(),
                'creditsUsed' => abs((int) (clone $generationTransactions)->sum('amount')),
            ],
        ]);
    }
}
