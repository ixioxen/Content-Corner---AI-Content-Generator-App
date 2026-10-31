<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CreditTransactionType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\InsufficientCreditsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustUserCreditsRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\AdminAuditLog;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(array_column(UserStatus::cases(), 'value'))],
        ]);

        $users = User::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $filters,
            'currentAdminId' => $request->user()->id,
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $nextStatus = UserStatus::from((string) $request->validated('status'));
        $admin = $request->user();

        abort_unless($admin instanceof User, 403);

        if ($user->is($admin) && $nextStatus === UserStatus::Suspended) {
            throw ValidationException::withMessages([
                'status' => 'You cannot suspend your own admin account.',
            ]);
        }

        DB::transaction(function () use ($admin, $nextStatus, $request, $user): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            $previousStatus = $target->status;

            if ($previousStatus === $nextStatus) {
                return;
            }

            if ($target->role === UserRole::Admin && $previousStatus === UserStatus::Active && $nextStatus === UserStatus::Suspended) {
                $hasAnotherActiveAdmin = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('status', UserStatus::Active->value)
                    ->whereKeyNot($target->id)
                    ->exists();

                if (! $hasAnotherActiveAdmin) {
                    throw ValidationException::withMessages([
                        'status' => 'The last active admin cannot be suspended.',
                    ]);
                }
            }

            $target->status = $nextStatus;
            $target->save();

            AdminAuditLog::query()->create([
                'admin_id' => $admin->id,
                'action' => $nextStatus === UserStatus::Suspended ? 'user.suspended' : 'user.activated',
                'target_type' => User::class,
                'target_id' => $target->id,
                'metadata' => [
                    'previous_status' => $previousStatus->value,
                    'status' => $nextStatus->value,
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User status updated.']);

        return back();
    }

    public function adjustCredits(AdjustUserCreditsRequest $request, User $user, CreditService $credits): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $amount = (int) $request->validated('amount');
        $idempotencyKey = 'admin_adjustment:'.$admin->id.':'.$user->id.':'.(string) $request->validated('idempotency_key');
        $reason = trim((string) $request->validated('reason'));

        try {
            DB::transaction(function () use ($admin, $amount, $credits, $idempotencyKey, $reason, $request, $user): void {
                $target = User::query()->lockForUpdate()->findOrFail($user->id);
                $existing = CreditTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existing !== null) {
                    if ($existing->amount !== $amount || $existing->description !== $reason) {
                        throw ValidationException::withMessages([
                            'idempotency_key' => 'This adjustment request was already used with different values.',
                        ]);
                    }

                    return;
                }

                $transaction = $credits->apply(
                    user: $target,
                    amount: $amount,
                    type: CreditTransactionType::AdminAdjustment,
                    idempotencyKey: $idempotencyKey,
                    description: $reason,
                    createdByAdminId: $admin->id,
                );

                AdminAuditLog::query()->create([
                    'admin_id' => $admin->id,
                    'action' => 'credits.adjusted',
                    'target_type' => User::class,
                    'target_id' => $user->id,
                    'metadata' => [
                        'amount' => $amount,
                        'reason' => $reason,
                        'transaction_id' => $transaction->id,
                    ],
                    'ip_address' => $request->ip(),
                ]);
            });
        } catch (InsufficientCreditsException) {
            throw ValidationException::withMessages([
                'amount' => 'This adjustment would make the user credit balance negative.',
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Credit balance adjusted.']);

        return back();
    }
}
