<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::get('articles', [ArticleController::class, 'index']);
Route::get('articles/{id}', [ArticleController::class, 'show']);

Route::middleware('auth:api')->group(function () {
    Route::post('articles', [ArticleController::class, 'store']);
    Route::put('articles/{id}', [ArticleController::class, 'update']);
    Route::delete('articles/{id}', [ArticleController::class, 'destroy']);
    Route::post('articles/{id}/submit', [ArticleController::class, 'submit']);
    Route::post('articles/{id}/appeal', [ArticleController::class, 'appeal']);
    Route::post('articles/{id}/resubmit', [ArticleController::class, 'resubmit']);
    Route::get('articles/{id}/review-logs', [ArticleController::class, 'reviewLogs']);
});

Route::get('categories', [CategoryController::class, 'index']);
Route::get('tags', [TagController::class, 'index']);
