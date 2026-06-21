<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Branch;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'balance' => fake()->randomFloat(2, 0, 100000),
        ];
    }
}
