<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Middleware\IsAdmin;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StripeWebhookController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/orders', [OrderController::class, 'store']);

    Route::post('/checkout', [StripeController::class, 'createCheckoutSession']);

});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);



// Продукты

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/paginated', [ProductController::class, 'getPaginatedProducts']);
Route::get('/products/tags', [ProductController::class, 'getByTag']);

Route::get('/products/{id}', [ProductController::class, 'show']);


Route::middleware([IsAdmin::class])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});



// Корзины пользователей

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{productId}', [CartController::class, 'update']);
    Route::delete('/cart/{productId}', [CartController::class, 'destroy']);
});
