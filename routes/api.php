<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FilterController;


Route::prefix('/catalog')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/filters', [FilterController::class, 'index']);
});

