<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Author\PortofolioController;
use App\Http\Controllers\Author\ProductController;
use App\Http\Controllers\Author\UploadContorller;
use App\Http\Controllers\Author\UploadController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/marketplace/home', [\App\Http\Controllers\Public\MarketplaceController::class, 'home']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [\App\Http\Controllers\Public\CartController::class, 'index']);
    Route::post('/cart/items', [\App\Http\Controllers\Public\CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [\App\Http\Controllers\Public\CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [\App\Http\Controllers\Public\CartController::class, 'destroy']);
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index'])->middleware('admin');
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings/{group}', [SettingsController::class, 'update']);
        Route::post('/settings/mail/test', [SettingsController::class, 'testMail']);

        Route::get('/products', [AdminProductController::class, 'index'])->middleware('admin');
        Route::get('/review-queue', [\App\Http\Controllers\Admin\ProductReviewController::class, 'queue']);
        Route::get('/products/{product}/download', [\App\Http\Controllers\Admin\ProductReviewController::class, 'download']);
        Route::get('/reviewers', [\App\Http\Controllers\Admin\ProductReviewController::class, 'reviewers']);
        Route::put('/reviewers/{user}/permission', [\App\Http\Controllers\Admin\ProductReviewController::class, 'permission']);

        Route::get('/categories', [CategoryController::class, 'index'])->middleware('admin');
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('admin');
        Route::delete('/categories/{id}', [CategoryController::class, 'delete'])->middleware('admin');
        Route::get('/categories/edit/{id}', [CategoryController::class, 'edit'])->middleware('admin');
        Route::put('/categories/edit/{id}', [CategoryController::class, 'update'])->middleware('admin');
        Route::get('/categories/parents', [CategoryController::class, 'parents'])->middleware('admin');
    });

    Route::get('admin/products/{product}/review', [\App\Http\Controllers\Admin\ProductReviewController::class, 'detail']);
    Route::post('admin/products/{product}/review/messages', [\App\Http\Controllers\Admin\ProductReviewController::class, 'message']);

    Route::prefix('author')->middleware('author')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create']);
        Route::post('/products/create', [ProductController::class, 'store']);
        Route::post('/products/{product}/updates', [\App\Http\Controllers\Author\ProductUpdateController::class, 'store']);
        Route::post('/products/{product}/updates/{version}/submit', [\App\Http\Controllers\Author\ProductUpdateController::class, 'submit']);
        Route::post('/uploads/presigned-url', [UploadController::class, 'presignedUrl']);
        Route::delete(
            '/uploads',
            [UploadController::class, 'destroy']
        );

        Route::get('dashboard',[PortofolioController::class,'dashboard']);
        Route::get('portfolio',[PortofolioController::class,'portfolio']);
        Route::get('portfolio/{product:slug}',[PortofolioController::class,'show']);
        Route::post('products/{product}/review/messages', [\App\Http\Controllers\Admin\ProductReviewController::class, 'message']);
    });
});

Route::get('products/{product:slug}',[PortofolioController::class,'publicShow']);
Route::get('authors/{author}/portfolio',[PortofolioController::class,'publicPortfolio']);

Route::post('admin/products/{product}/review', [\App\Http\Controllers\Admin\ProductReviewController::class, 'decide'])->middleware('auth:sanctum');

Route::get('testing', function () {
    return response()->json("LFKdslkfj");
});
