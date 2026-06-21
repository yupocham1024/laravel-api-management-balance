<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function access(User $user, Account $account): bool
    {
        return $user->id === $account->user_id;
    }
}
