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
Route::get('/marketplace/search', [\App\Http\Controllers\Public\MarketplaceController::class, 'search']);
Route::post('/purchase-verifications', [\App\Http\Controllers\Public\PurchaseVerificationController::class, 'verify'])->middleware('throttle:30,1');
Route::post('/payments/stripe/webhook', [\App\Http\Controllers\Public\CheckoutController::class, 'stripeWebhook']);
Route::post('/payments/razorpay/webhook', [\App\Http\Controllers\Public\CheckoutController::class, 'razorpayWebhook']);
Route::post('/payments/paypal/webhook', [\App\Http\Controllers\Public\CheckoutController::class, 'paypalWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [\App\Http\Controllers\Public\CartController::class, 'index']);
    Route::post('/cart/items', [\App\Http\Controllers\Public\CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [\App\Http\Controllers\Public\CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [\App\Http\Controllers\Public\CartController::class, 'destroy']);
    Route::get('/checkout/methods', [\App\Http\Controllers\Public\CheckoutController::class, 'methods']);
    Route::get('/checkout/summary', [\App\Http\Controllers\Public\CheckoutController::class, 'summary']);
    Route::post('/checkout', [\App\Http\Controllers\Public\CheckoutController::class, 'start']);
    Route::post('/checkout/stripe/confirm', [\App\Http\Controllers\Public\CheckoutController::class, 'confirmStripe']);
    Route::post('/checkout/razorpay/verify', [\App\Http\Controllers\Public\CheckoutController::class, 'verifyRazorpay']);
    Route::post('/checkout/paypal/capture', [\App\Http\Controllers\Public\CheckoutController::class, 'capturePayPal']);
    Route::get('/checkout/orders/{order:public_id}', [\App\Http\Controllers\Public\CheckoutController::class, 'show']);
    Route::get('/purchases', [\App\Http\Controllers\Public\CheckoutController::class, 'purchases']);
    Route::get('/support/tickets', [\App\Http\Controllers\Public\SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [\App\Http\Controllers\Public\SupportTicketController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/support/tickets/{ticket}', [\App\Http\Controllers\Public\SupportTicketController::class, 'show']);
    Route::post('/support/tickets/{ticket}/messages', [\App\Http\Controllers\Public\SupportTicketController::class, 'message'])->middleware('throttle:40,1');
    Route::post('/support/tickets/{ticket}/close', [\App\Http\Controllers\Public\SupportTicketController::class, 'close']);
    Route::get('/finance/customer-statement', [\App\Http\Controllers\Finance\FinanceController::class, 'customerStatement']);
    Route::get('/finance/invoices/{order:public_id}', [\App\Http\Controllers\Finance\FinanceController::class, 'invoice']);
    Route::get('/finance/author-statement', [\App\Http\Controllers\Finance\FinanceController::class, 'authorStatement']);
    Route::get('/admin/finance/overview', [\App\Http\Controllers\Finance\FinanceController::class, 'adminOverview']);
    Route::post('/downloads/{entitlement}', [\App\Http\Controllers\Public\CheckoutController::class, 'download']);
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index'])->middleware('admin');
        Route::get('/agents', [\App\Http\Controllers\Admin\AgentController::class, 'index']);
        Route::get('/commission-tiers', [\App\Http\Controllers\Admin\CommissionTierController::class, 'index']);
        Route::post('/commission-tiers', [\App\Http\Controllers\Admin\CommissionTierController::class, 'store']);
        Route::put('/commission-tiers/{commissionTier}', [\App\Http\Controllers\Admin\CommissionTierController::class, 'update']);
        Route::post('/agents', [\App\Http\Controllers\Admin\AgentController::class, 'store']);
        Route::put('/agents/{user}', [\App\Http\Controllers\Admin\AgentController::class, 'update']);
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings/{group}', [SettingsController::class, 'update']);
        Route::post('/settings/mail/test', [SettingsController::class, 'testMail']);

        Route::get('/products', [AdminProductController::class, 'index'])->middleware('admin');
        Route::get('/review-queue', [\App\Http\Controllers\Admin\ProductReviewController::class, 'queue']);
        Route::get('/products/{product}/download', [\App\Http\Controllers\Admin\ProductReviewController::class, 'download']);
        Route::get('/reviewers', [\App\Http\Controllers\Admin\ProductReviewController::class, 'reviewers']);
        Route::put('/reviewers/{user}/permission', [\App\Http\Controllers\Admin\ProductReviewController::class, 'permission']);

        Route::get('/licenses', [\App\Http\Controllers\Admin\LicenseController::class, 'index'])->middleware('admin');
        Route::post('/licenses', [\App\Http\Controllers\Admin\LicenseController::class, 'store'])->middleware('admin');
        Route::put('/licenses/reorder', [\App\Http\Controllers\Admin\LicenseController::class, 'reorder'])->middleware('admin');
        Route::put('/licenses/{license}', [\App\Http\Controllers\Admin\LicenseController::class, 'update'])->middleware('admin');
        Route::get('/categories', [CategoryController::class, 'index'])->middleware('admin');
        Route::get('/categories/form-options', [CategoryController::class, 'formOptions'])->middleware('admin');
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
