<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'creditsBalance' => $user->credits_balance,
            'recentTransactions' => $user->creditTransactions()
                ->latest('created_at')
                ->limit(5)
                ->get(['id', 'amount', 'type', 'description', 'created_at']),
            'recentGenerations' => $user->generatedContents()
                ->where('status', 'completed')
                ->latest()
                ->limit(3)
                ->get(['id', 'content_type', 'brief', 'created_at']),
        ]);
    }
}
