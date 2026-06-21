<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    // 支店作成: 正常系
    public function test_can_create_branch(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/branches', [
            'name'       => '東京支店',
            'prefecture' => '東京都',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => '東京支店']);
    }

    // 支店作成: name が欠けている
    public function test_returns_validation_error_when_name_is_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/branches', [
            'prefecture' => '東京都',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    // 支店作成: prefecture が欠けている
    public function test_returns_validation_error_when_prefecture_is_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/branches', [
            'name' => '東京支店',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['prefecture']);
    }

    // 支店単体取得: 存在する
    public function test_can_get_branch(): void
    {
        $user = User::factory()->create();

        $branch = Branch::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/branches/{$branch->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $branch->id]);
    }

    // 支店単体取得: 存在しない
    public function test_returns_404_when_branch_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/branches/99999');

        $response->assertStatus(404);
    }

    // 支店一覧取得: 存在する
    public function test_can_get_branch_list(): void
    {
        $user = User::factory()->create();

        Branch::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/branches');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    // 支店一覧取得: 存在しない
    public function test_returns_empty_array_when_no_branches_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/branches');

        $response->assertStatus(200)
                 ->assertExactJson([]);
    }
}
