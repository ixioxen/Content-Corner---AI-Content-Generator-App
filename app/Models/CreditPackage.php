<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'credits_amount', 'price_amount', 'currency', 'is_active'])]
class CreditPackage extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits_amount' => 'integer',
            'price_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CreditPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class);
    }
}
