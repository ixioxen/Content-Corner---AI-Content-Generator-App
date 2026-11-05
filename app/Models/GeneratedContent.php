<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $brand_profile_id
 * @property int|null $custom_tone_id
 * @property string $request_uuid
 * @property string $content_type
 * @property string $brief
 * @property array<string, mixed>|null $brand_profile_snapshot
 * @property array<string, mixed>|null $custom_tone_snapshot
 * @property string|null $generated_text
 * @property string $status
 * @property string|null $failure_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'brand_profile_id',
    'custom_tone_id',
    'request_uuid',
    'content_type',
    'brief',
    'brand_profile_snapshot',
    'custom_tone_snapshot',
    'generated_text',
    'status',
    'failure_message',
])]
class GeneratedContent extends Model
{
    protected function casts(): array
    {
        return [
            'brand_profile_snapshot' => 'array',
            'custom_tone_snapshot' => 'array',
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
     * @return BelongsTo<BrandProfile, $this>
     */
    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class);
    }

    /**
     * @return BelongsTo<CustomTone, $this>
     */
    public function customTone(): BelongsTo
    {
        return $this->belongsTo(CustomTone::class);
    }
}
