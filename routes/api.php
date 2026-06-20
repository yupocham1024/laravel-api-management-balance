<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::post('/accounts', [AccountController::class, 'store']);
Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);
Route::post('/accounts/{account}/deposit', [AccountController::class, 'deposit']);
Route::post('/accounts/{account}/withdraw', [AccountController::class, 'withdraw']);
Route::get('/accounts/{account}/transactions', [AccountController::class, 'transactions']);
