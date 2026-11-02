<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $brand_name
 * @property string|null $industry
 * @property string|null $target_audience
 * @property string|null $personality
 * @property string|null $writing_style
 * @property array<int, string>|null $preferred_words
 * @property array<int, string>|null $avoid_words
 * @property string $emoji_usage
 * @property string $language
 * @property string|null $main_platform
 * @property string|null $pain_points
 * @property string|null $additional_instructions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'brand_name',
    'industry',
    'target_audience',
    'personality',
    'writing_style',
    'preferred_words',
    'avoid_words',
    'emoji_usage',
    'language',
    'main_platform',
    'pain_points',
    'additional_instructions',
])]
class BrandProfile extends Model
{
    protected function casts(): array
    {
        return [
            'preferred_words' => 'array',
            'avoid_words' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
