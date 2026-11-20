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
 * @property array<string, mixed>|null $brand_profile_snapshot
 * @property array<string, mixed>|null $custom_tone_snapshot
 * @property string|null $generate_script_text
 * @property string|null $failure_message
 * @property string $brief
 * @property string $request_uuid
 * @property string $script_type
 * @property array<string, mixed>|null $input_data
 * @property array<string, mixed>|null $output_data
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'brand_profile_id',
    'custom_tone_id',
    'brand_profile_snapshot',
    'custom_tone_snapshot',
    'generate_script_text',
    'failure_message',
    'brief',
    'request_uuid',
    'script_type',
    'input_data',
    'output_data',
    'status',
   
])]
class ScriptGenerate extends Model
{

    protected function casts(): array
    {
        return [
            'brand_profile_snapshot' => 'array',
            'custom_tone_snapshot'   => 'array',
            'input_data'             => 'array',
            'output_data'            => 'array',
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
