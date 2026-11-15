<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property UserStatus $status
 * @property int $credits_balance
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Convert database values into the PHP types used by the app.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'credits_balance' => 'integer',
        ];
    }

    /**
     * Get this user's credit transactions.
     *
     * @return HasMany<CreditTransaction, $this>
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get this user's credit purchases.
     *
     * @return HasMany<CreditPurchase, $this>
     */
    public function creditPurchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class);
    }

    /**
     * Get this user's custom tones.
     *
     * @return HasMany<CustomTone, $this>
     */
    public function customTones(): HasMany
    {
        return $this->hasMany(CustomTone::class);
    }

    /**
     * Get this user's custom tone undo actions.
     *
     * @return HasMany<CustomToneUndoAction, $this>
     */
    public function customToneUndoActions(): HasMany
    {
        return $this->hasMany(CustomToneUndoAction::class);
    }

    /**
     * Get this user's brand profiles.
     *
     * @return HasMany<BrandProfile, $this>
     */
    public function brandProfiles(): HasMany
    {
        return $this->hasMany(BrandProfile::class);
    }

    /**
     * Get this user's brand profile undo actions.
     *
     * @return HasMany<BrandProfileUndoAction, $this>
     */
    public function brandProfileUndoActions(): HasMany
    {
        return $this->hasMany(BrandProfileUndoAction::class);
    }

    /**
     * Get this user's generated content.
     *
     * @return HasMany<GeneratedContent, $this>
     */
    public function generatedContents(): HasMany
    {
        return $this->hasMany(GeneratedContent::class);
    }

    /**
     * Check whether this user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Check whether this user's account is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Check whether this user's account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }
}
