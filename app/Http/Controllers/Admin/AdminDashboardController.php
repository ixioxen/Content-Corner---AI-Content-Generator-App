<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CreditTransactionType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\CreditTransaction;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $generationTransactions = CreditTransaction::query()
            ->where('type', CreditTransactionType::Generation->value)
            ->where('amount', '<', 0);

        return Inertia::render('Admin/Overview', [
            'metrics' => [
                'users' => User::query()->where('role', 'user')->count(),
                'activeUsers' => User::query()->where('role', 'user')->where('status', UserStatus::Active->value)->count(),
                'suspendedUsers' => User::query()->where('role', 'user')->where('status', UserStatus::Suspended->value)->count(),
                'creditsInCirculation' => (int) User::query()->sum('credits_balance'),
                'generations' => (clone $generationTransactions)->count(),
                'creditsUsed' => abs((int) (clone $generationTransactions)->sum('amount')),
            ],
            'recentTransactions' => CreditTransaction::query()
                ->with('user:id,name,email')
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'user_id', 'amount', 'type', 'description', 'created_at']),
            'recentAuditLogs' => AdminAuditLog::query()
                ->with('admin:id,name,email')
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'admin_id', 'action', 'target_type', 'target_id', 'metadata', 'created_at']),
        ]);
    }
}
