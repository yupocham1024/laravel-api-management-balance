<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 認証不要
Route::post('/users', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

// 認証必要
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);
    Route::post('/accounts/{account}/deposit', [AccountController::class, 'deposit']);
    Route::post('/accounts/{account}/withdraw', [AccountController::class, 'withdraw']);
    Route::get('/accounts/{account}/transactions', [AccountController::class, 'transactions']);
});
