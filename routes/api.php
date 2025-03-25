<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Продукты

Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/{id}', [ProductController::class, 'show']);

Route::post('/products', [ProductController::class, 'store']);

Route::put('/products/{product}', [ProductController::class, 'update']);

Route::delete('/products/{id}', [ProductController::class, 'destroy']);

// Изображения

// Route::delete('/product-images/{id}', [ProductImageController::class, 'destroy']);