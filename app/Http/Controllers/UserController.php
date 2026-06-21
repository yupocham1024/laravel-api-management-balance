<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // ユーザーを新規作成して返す
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string'],
            'email'              => ['required', 'email', 'unique:users,email'],
            'password'           => ['required', 'string'],
            'age'                => ['required', 'integer', 'min:0'],
            'sex'                => ['required', 'in:male,female,other'],
            'address_prefecture' => ['required', 'string'],
            'address1'           => ['required', 'string'],
            'address2'           => ['nullable', 'string'],
        ]);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    // 指定ユーザーを返す
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    // ユーザー一覧を返す
    public function index(): JsonResponse
    {
        return response()->json(User::all());
    }
}
