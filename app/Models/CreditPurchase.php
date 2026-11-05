<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $credit_package_id
 * @property string $package_name
 * @property int $credits_amount
 * @property string $price_amount
 * @property string $currency
 * @property string $status
 * @property string|null $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 */
#[Fillable([
    'user_id',
    'credit_package_id',
    'package_name',
    'credits_amount',
    'price_amount',
    'currency',
    'status',
    'stripe_checkout_session_id',
    'stripe_payment_intent_id',
    'paid_at',
    'failed_at',
])]
class CreditPurchase extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits_amount' => 'integer',
            'price_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CreditPackage, $this>
     */
    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }
}
