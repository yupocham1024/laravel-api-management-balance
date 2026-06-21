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

        $response = $this->actingAs($user)->postJson('/api/accounts', [
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['user_id' => $user->id, 'branch_id' => $branch->id]);
    }

    // 口座作成: branch_id が欠けている
    public function test_returns_validation_error_when_branch_id_is_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/accounts', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['branch_id']);
    }

    // 口座作成: 存在しない branch_id
    public function test_returns_validation_error_when_branch_does_not_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/accounts', [
            'branch_id' => 99999,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['branch_id']);
    }

    // 残高取得: 正常系
    public function test_can_get_balance(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 5000]);

        $response = $this->actingAs($user)->getJson("/api/accounts/{$account->id}/balance");

        $response->assertStatus(200)
                 ->assertJsonFragment(['account_id' => $account->id]);
    }

    // 残高取得: 存在しない口座
    public function test_returns_404_when_account_not_found_on_balance(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/accounts/99999/balance');

        $response->assertStatus(404);
    }

    // 残高取得: 他ユーザーの口座
    public function test_returns_403_when_accessing_other_users_account_balance(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->getJson("/api/accounts/{$account->id}/balance");

        $response->assertStatus(403);
    }

    // 入金: 正常系
    public function test_can_deposit(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000]);

        $response = $this->actingAs($user)->postJson("/api/accounts/{$account->id}/deposit", [
            'amount' => 500,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['balance' => '1500.00']);
    }

    // 入金: amount が欠けている
    public function test_returns_validation_error_when_deposit_amount_is_missing(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson("/api/accounts/{$account->id}/deposit", []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    // 入金: amount が 0
    public function test_returns_validation_error_when_deposit_amount_is_zero(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson("/api/accounts/{$account->id}/deposit", [
            'amount' => 0,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    // 入金: 他ユーザーの口座
    public function test_returns_403_when_depositing_to_other_users_account(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->postJson("/api/accounts/{$account->id}/deposit", [
            'amount' => 500,
        ]);

        $response->assertStatus(403);
    }

    // 出金: 正常系
    public function test_can_withdraw(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000]);

        $response = $this->actingAs($user)->postJson("/api/accounts/{$account->id}/withdraw", [
            'amount' => 300,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['balance' => '700.00']);
    }

    // 出金: 残高不足
    public function test_returns_422_when_balance_is_insufficient(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 100]);

        $response = $this->actingAs($user)->postJson("/api/accounts/{$account->id}/withdraw", [
            'amount' => 500,
        ]);

        $response->assertStatus(422);
    }

    // 取引履歴取得: 正常系
    public function test_can_get_transactions(): void
    {
        $user    = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000]);

        $this->actingAs($user)->postJson("/api/accounts/{$account->id}/deposit", ['amount' => 500]);
        $this->actingAs($user)->postJson("/api/accounts/{$account->id}/withdraw", ['amount' => 200]);

        $response = $this->actingAs($user)->getJson("/api/accounts/{$account->id}/transactions");

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    // 取引履歴取得: 存在しない口座
    public function test_returns_404_when_account_not_found_on_transactions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/accounts/99999/transactions');

        $response->assertStatus(404);
    }
}
