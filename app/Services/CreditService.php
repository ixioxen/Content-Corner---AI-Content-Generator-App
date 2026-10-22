<?php

namespace App\Services;

use App\Enums\CreditTransactionType;
use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditService
{
    public function grantSignupBonus(User $user): CreditTransaction
    {
        return $this->apply(
            user: $user,
            amount: (int) config('credits.signup_bonus'),
            type: CreditTransactionType::SignupBonus,
            idempotencyKey: 'signup_bonus:'.$user->id,
            description: 'Signup bonus',
        );
    }

    public function apply(
        User $user,
        int $amount,
        CreditTransactionType $type,
        string $idempotencyKey,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdByAdminId = null,
    ): CreditTransaction {
        if ($amount === 0) {
            throw new InvalidArgumentException('Credit amount must not be zero.');
        }

        try {
            return DB::transaction(function () use (
                $user,
                $amount,
                $type,
                $idempotencyKey,
                $description,
                $referenceType,
                $referenceId,
                $createdByAdminId,
            ): CreditTransaction {
                // Reusing a key returns the original ledger row instead of applying credits twice.
                $existing = CreditTransaction::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }

                // Lock the account so simultaneous requests cannot spend the same balance.
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $newBalance = $lockedUser->credits_balance + $amount;

                if ($newBalance < 0) {
                    throw new InsufficientCreditsException;
                }

                $lockedUser->credits_balance = $newBalance;
                $lockedUser->save();

                // This ledger insert shares the balance update's transaction.
                return CreditTransaction::query()->create([
                    'user_id' => $lockedUser->id,
                    'amount' => $amount,
                    'type' => $type,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'idempotency_key' => $idempotencyKey,
                    'description' => $description,
                    'created_by_admin_id' => $createdByAdminId,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent request may have inserted the same key; return its recorded result.
            return CreditTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();
        }
    }
}
