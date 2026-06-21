<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AccountController extends Controller
{
    use AuthorizesRequests;

    // 口座を新規作成して返す
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $account = Account::create([
            'user_id'   => $request->user()->id,
            'branch_id' => $data['branch_id'],
            'balance'   => 0,
        ]);

        return response()->json($account, 201);
    }

    // 指定口座の残高を返す
    public function balance(Account $account): JsonResponse
    {
        $this->authorize('access', $account);

        return response()->json([
            'account_id' => $account->id,
            'balance'    => $account->balance,
        ]);
    }

    // 指定口座に入金して更新後の残高を返す
    public function deposit(Request $request, Account $account): JsonResponse
    {
        $this->authorize('access', $account);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($account, $data) {
            $account->increment('balance', $data['amount']); // 残高を増やす
            $account->transactions()->create([ // 取引履歴を記録する
                'type'   => 'deposit',
                'amount' => $data['amount'],
            ]);
        });

        return response()->json([
            'account_id' => $account->id,
            'balance'    => $account->fresh()->balance,
        ]);
    }

    // 指定口座から出金して更新後の残高を返す（残高不足なら 422 を返す）
    public function withdraw(Request $request, Account $account): JsonResponse
    {
        $this->authorize('access', $account);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($account->balance < $data['amount']) {
            return response()->json(['message' => '残高が不足しています。'], 422);
        }

        DB::transaction(function () use ($account, $data) {
            $account->decrement('balance', $data['amount']); // 残高を減らす
            $account->transactions()->create([ // 取引履歴を記録する
                'type'   => 'withdraw',
                'amount' => $data['amount'],
            ]);
        });

        return response()->json([
            'account_id' => $account->id,
            'balance'    => $account->fresh()->balance,
        ]);
    }

    // 指定口座の取引履歴を新しい順で返す
    public function transactions(Account $account): JsonResponse
    {
        $this->authorize('access', $account);

        return response()->json(
            $account->transactions()->latest()->get()
        );
    }
}
