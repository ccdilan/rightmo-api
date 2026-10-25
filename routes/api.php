<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('categories', CategoryController::class)->name('categories.index');
        Route::post('products/{product}/image', ProductImageController::class)->whereNumber('product')->name('products.image');
        Route::put('products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.replace');
        Route::patch('products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::apiResource('products', ProductController::class)->except('update')->where(['product' => '[0-9]+']);
    });
});
