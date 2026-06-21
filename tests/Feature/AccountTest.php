<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    // 口座作成: 正常系
    public function test_can_create_account(): void
    {
        $user   = User::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->postJson('/api/accounts', [
            'user_id'   => $user->id,
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['user_id' => $user->id, 'branch_id' => $branch->id]);
    }

    // 口座作成: user_id が欠けている
    public function test_returns_validation_error_when_user_id_is_missing(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->postJson('/api/accounts', [
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['user_id']);
    }

    // 口座作成: 存在しない user_id
    public function test_returns_validation_error_when_user_does_not_exist(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->postJson('/api/accounts', [
            'user_id'   => 99999,
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['user_id']);
    }

    // 残高取得: 正常系
    public function test_can_get_balance(): void
    {
        $account = Account::factory()->create(['balance' => 5000]);

        $response = $this->getJson("/api/accounts/{$account->id}/balance");

        $response->assertStatus(200)
                 ->assertJsonFragment(['account_id' => $account->id]);
    }

    // 残高取得: 存在しない口座
    public function test_returns_404_when_account_not_found_on_balance(): void
    {
        $response = $this->getJson('/api/accounts/99999/balance');

        $response->assertStatus(404);
    }

    // 入金: 正常系
    public function test_can_deposit(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);

        $response = $this->postJson("/api/accounts/{$account->id}/deposit", [
            'amount' => 500,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['balance' => '1500.00']);
    }

    // 入金: amount が欠けている
    public function test_returns_validation_error_when_deposit_amount_is_missing(): void
    {
        $account = Account::factory()->create();

        $response = $this->postJson("/api/accounts/{$account->id}/deposit", []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    // 入金: amount が 0
    public function test_returns_validation_error_when_deposit_amount_is_zero(): void
    {
        $account = Account::factory()->create();

        $response = $this->postJson("/api/accounts/{$account->id}/deposit", [
            'amount' => 0,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    // 出金: 正常系
    public function test_can_withdraw(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);

        $response = $this->postJson("/api/accounts/{$account->id}/withdraw", [
            'amount' => 300,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['balance' => '700.00']);
    }

    // 出金: 残高不足
    public function test_returns_422_when_balance_is_insufficient(): void
    {
        $account = Account::factory()->create(['balance' => 100]);

        $response = $this->postJson("/api/accounts/{$account->id}/withdraw", [
            'amount' => 500,
        ]);

        $response->assertStatus(422);
    }

    // 取引履歴取得: 正常系
    public function test_can_get_transactions(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);

        $this->postJson("/api/accounts/{$account->id}/deposit", ['amount' => 500]);
        $this->postJson("/api/accounts/{$account->id}/withdraw", ['amount' => 200]);

        $response = $this->getJson("/api/accounts/{$account->id}/transactions");

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    // 取引履歴取得: 存在しない口座
    public function test_returns_404_when_account_not_found_on_transactions(): void
    {
        $response = $this->getJson('/api/accounts/99999/transactions');

        $response->assertStatus(404);
    }
}
