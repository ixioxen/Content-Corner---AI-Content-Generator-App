<?php

namespace Tests\Feature\Credits;

use App\Enums\CreditTransactionType;
use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_updates_balance_and_creates_ledger_row(): void
    {
        $user = User::factory()->create(['credits_balance' => 5]);

        $transaction = app(CreditService::class)->apply(
            user: $user,
            amount: -1,
            type: CreditTransactionType::Generation,
            idempotencyKey: 'generation:test-1',
            description: 'Test charge',
        );

        $user->refresh();

        $this->assertSame(4, $user->credits_balance);
        $this->assertSame(-1, $transaction->amount);
        $this->assertSame(CreditTransactionType::Generation, $transaction->type);
        $this->assertDatabaseCount('credit_transactions', 1);
    }

    public function test_apply_rejects_negative_balance(): void
    {
        $user = User::factory()->create(['credits_balance' => 0]);

        try {
            app(CreditService::class)->apply(
                user: $user,
                amount: -1,
                type: CreditTransactionType::Generation,
                idempotencyKey: 'generation:test-2',
            );

            $this->fail('Expected InsufficientCreditsException.');
        } catch (InsufficientCreditsException) {
            $this->assertSame(0, $user->fresh()->credits_balance);
            $this->assertDatabaseCount('credit_transactions', 0);
        }
    }

    public function test_duplicate_idempotency_key_does_not_change_balance_twice(): void
    {
        $user = User::factory()->create(['credits_balance' => 10]);
        $credits = app(CreditService::class);

        $first = $credits->apply(
            user: $user,
            amount: -1,
            type: CreditTransactionType::Generation,
            idempotencyKey: 'generation:same',
        );

        $second = $credits->apply(
            user: $user,
            amount: -1,
            type: CreditTransactionType::Generation,
            idempotencyKey: 'generation:same',
        );

        $this->assertTrue($first->is($second));
        $this->assertSame(9, $user->fresh()->credits_balance);
        $this->assertSame(1, CreditTransaction::query()->count());
    }
}
