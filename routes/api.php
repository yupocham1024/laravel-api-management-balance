<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// users
Route::post('/users', [UserController::class, 'store']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);

// branches
Route::post('/branches', [BranchController::class, 'store']);
Route::get('/branches', [BranchController::class, 'index']);
Route::get('/branches/{branch}', [BranchController::class, 'show']);

// accounts
Route::post('/accounts', [AccountController::class, 'store']);
Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);
Route::post('/accounts/{account}/deposit', [AccountController::class, 'deposit']);
Route::post('/accounts/{account}/withdraw', [AccountController::class, 'withdraw']);
Route::get('/accounts/{account}/transactions', [AccountController::class, 'transactions']);
