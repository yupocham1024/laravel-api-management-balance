<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // 支店を新規作成して返す
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string'],
            'prefecture' => ['required', 'string'],
        ]);

        $branch = Branch::create($data);

        return response()->json($branch, 201);
    }

    // 指定支店を返す
    public function show(Branch $branch): JsonResponse
    {
        return response()->json($branch);
    }

    // 支店一覧を返す
    public function index(): JsonResponse
    {
        return response()->json(Branch::all());
    }
}
