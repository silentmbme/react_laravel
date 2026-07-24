<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Author\ProductController;
use App\Http\Controllers\Author\UploadContorller;
use App\Http\Controllers\Author\UploadController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::delete('/categories/{id}', [CategoryController::class, 'delete']);
        Route::get('/categories/edit/{id}', [CategoryController::class, 'edit']);
        Route::put('/categories/edit/{id}', [CategoryController::class, 'update']);
        Route::get('/categories/parents', [CategoryController::class, 'parents']);
    });

    Route::prefix('author')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create']);
        Route::post('/products/create', [ProductController::class, 'store']);
        Route::post('/uploads/presigned-url', [UploadController::class, 'presignedUrl']);
        Route::delete(
            '/uploads',
            [UploadController::class, 'destroy']
        );
    });
});

Route::get('testing', function () {
    return response()->json("LFKdslkfj");
});
