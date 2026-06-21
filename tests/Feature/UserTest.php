<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ユーザー作成: 正常系
    public function test_can_create_user(): void
    {
        $response = $this->postJson('/api/users', [
            'name'               => '田中太郎',
            'email'              => 'tanaka@example.com',
            'password'           => 'password',
            'age'                => 30,
            'sex'                => 'male',
            'address_prefecture' => '東京都',
            'address1'           => '新宿区1-1-1',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['email' => 'tanaka@example.com']);
    }

    // ユーザー作成: name が null
    public function test_returns_validation_error_when_name_is_missing(): void
    {
        $response = $this->postJson('/api/users', [
            'email'              => 'tanaka@example.com',
            'password'           => 'password',
            'age'                => 30,
            'sex'                => 'male',
            'address_prefecture' => '東京都',
            'address1'           => '新宿区1-1-1',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    // ユーザー作成: email が不正な形式
    public function test_returns_validation_error_when_email_is_invalid(): void
    {
        $response = $this->postJson('/api/users', [
            'name'               => '田中太郎',
            'email'              => 'invalid-email',
            'password'           => 'password',
            'age'                => 30,
            'sex'                => 'male',
            'address_prefecture' => '東京都',
            'address1'           => '新宿区1-1-1',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    // ユーザー作成: email が重複している
    public function test_returns_validation_error_when_email_is_duplicate(): void
    {
        User::factory()->create(['email' => 'tanaka@example.com']);

        $response = $this->postJson('/api/users', [
            'name'               => '田中次郎',
            'email'              => 'tanaka@example.com',
            'password'           => 'password',
            'age'                => 30,
            'sex'                => 'male',
            'address_prefecture' => '東京都',
            'address1'           => '新宿区1-1-1',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    // ユーザー単体取得: 存在する
    public function test_can_get_user(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $user->id]);
    }

    // ユーザー単体取得: 存在しない
    public function test_returns_404_when_user_not_found(): void
    {
        $response = $this->getJson('/api/users/99999');

        $response->assertStatus(404);
    }

    // ユーザー全体取得: 存在する
    public function test_can_get_user_list(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    // ユーザー全体取得: 存在しない
    public function test_returns_empty_array_when_no_users_exist(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertExactJson([]);
    }
}
