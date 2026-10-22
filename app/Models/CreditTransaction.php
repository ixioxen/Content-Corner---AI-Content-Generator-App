<?php

namespace App\Models;

use App\Enums\CreditTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property CreditTransactionType $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $idempotency_key
 * @property string|null $description
 * @property int|null $created_by_admin_id
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id',
    'amount',
    'type',
    'reference_type',
    'reference_id',
    'idempotency_key',
    'description',
    'created_by_admin_id',
])]
class CreditTransaction extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => CreditTransactionType::class,
            'created_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }
}
