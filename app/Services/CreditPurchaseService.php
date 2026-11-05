<?php

namespace App\Services;

use App\Enums\CreditTransactionType;
use App\Models\CreditPackage;
use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditPurchaseService
{
    public function createPending(User $user, int $packageId): CreditPurchase
    {
        return DB::transaction(function () use ($packageId, $user): CreditPurchase {
            $package = CreditPackage::query()
                ->whereKey($packageId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($package === null) {
                throw ValidationException::withMessages([
                    'package' => 'This credit package is no longer available.',
                ]);
            }

            return $user->creditPurchases()->create([
                'credit_package_id' => $package->id,
                'package_name' => $package->name,
                'credits_amount' => $package->credits_amount,
                'price_amount' => $package->price_amount,
                'currency' => $package->currency,
                'status' => 'pending',
            ]);
        });
    }

    public function completeVerifiedPayment(
        CreditPurchase $purchase,
        CreditService $credits,
        ?string $checkoutSessionId = null,
        ?string $paymentIntentId = null,
    ): CreditPurchase {
        return DB::transaction(function () use ($checkoutSessionId, $credits, $paymentIntentId, $purchase): CreditPurchase {
            $lockedPurchase = CreditPurchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($lockedPurchase->status !== 'pending') {
                return $lockedPurchase;
            }

            $lockedPurchase->update([
                'status' => 'paid',
                'stripe_checkout_session_id' => $checkoutSessionId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'paid_at' => now(),
            ]);

            $credits->apply(
                user: $lockedPurchase->user,
                amount: $lockedPurchase->credits_amount,
                type: CreditTransactionType::Purchase,
                idempotencyKey: 'credit_purchase:'.$lockedPurchase->id.':grant',
                description: 'Credit package purchase',
                referenceType: CreditPurchase::class,
                referenceId: $lockedPurchase->id,
            );

            return $lockedPurchase;
        });
    }

    public function markPaymentFailed(CreditPurchase $purchase): CreditPurchase
    {
        return DB::transaction(function () use ($purchase): CreditPurchase {
            $lockedPurchase = CreditPurchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($lockedPurchase->status === 'pending') {
                $lockedPurchase->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                ]);
            }

            return $lockedPurchase;
        });
    }
}
