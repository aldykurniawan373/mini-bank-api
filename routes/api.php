<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/token/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);

    Route::middleware('role:pimpinan')->group(function () {
        Route::apiResource('users', UserController::class)
            ->except(['show']);
    });

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::patch('/customers/{customer}', [CustomerController::class, 'update']);
    });

    Route::middleware('role:pimpinan')->group(function () {
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/customers/{customer}/account', [AccountController::class, 'store']);
    });

    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/search', [AccountController::class, 'search']);
    Route::get('/accounts/{account}', [AccountController::class, 'show']);

    Route::get('/transactions', [TransactionController::class, 'index']);

    Route::prefix('transactions/{account}')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('/deposit', [TransactionController::class, 'deposit']);
            Route::post('/withdraw', [TransactionController::class, 'withdraw']);
            Route::post('/transfer', [TransactionController::class, 'transfer']);
            Route::post('/export', [TransactionController::class, 'export']);
        });

        Route::get('/balance', [TransactionController::class, 'balance']);
        Route::get('/history', [TransactionController::class, 'history']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/transactions/exports', [TransactionController::class, 'listExports']);
        Route::get('/transactions/{account}/exports', [TransactionController::class, 'listExports']);
        Route::get('/transactions/export/{filename}/status', [TransactionController::class, 'checkExportStatus']);
        Route::match(['get', 'post'], '/transactions/export/{filename}', [TransactionController::class, 'downloadExport']);
    });
});
