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
Route::post('/forgot-password', [\App\Http\Controllers\API\PasswordResetController::class, 'requestLink'])->middleware('throttle:5,1');
Route::post('/reset-password', [\App\Http\Controllers\API\PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
Route::get('/usernames/availability', [AuthController::class, 'usernameAvailability'])->middleware('throttle:30,1');
Route::get('/marketplace/home', [\App\Http\Controllers\Public\MarketplaceController::class, 'home']);
Route::get('/marketplace/theme', [\App\Http\Controllers\Public\MarketplaceController::class, 'theme']);
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
    Route::get('/notifications', [\App\Http\Controllers\Public\NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Public\NotificationController::class, 'readAll']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Public\NotificationController::class, 'read']);
    Route::get('/profile', [\App\Http\Controllers\Public\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\Public\ProfileController::class, 'update']);
    Route::put('/profile/password', [\App\Http\Controllers\Public\ProfileController::class, 'password']);
    Route::get('/finance/invoices/{order:public_id}', [\App\Http\Controllers\Finance\FinanceController::class, 'invoice']);
    Route::get('/finance/author-statement', [\App\Http\Controllers\Finance\FinanceController::class, 'authorStatement']);
    Route::get('/author/finance-profile', [\App\Http\Controllers\Finance\AuthorFinanceController::class, 'show']);
    Route::put('/author/finance-profile', [\App\Http\Controllers\Finance\AuthorFinanceController::class, 'update']);
    Route::get('/author/payouts', [\App\Http\Controllers\Finance\AuthorFinanceController::class, 'payouts']);
    Route::post('/author/payouts/request', [\App\Http\Controllers\Finance\AuthorFinanceController::class, 'requestPayout']);
    Route::get('/admin/finance/overview', [\App\Http\Controllers\Finance\FinanceController::class, 'adminOverview']);
    Route::get('/admin/finance/summary', [\App\Http\Controllers\Finance\FinancialAdminController::class, 'summary']);
    Route::get('/admin/finance/transactions', [\App\Http\Controllers\Finance\FinancialAdminController::class, 'transactions']);
    Route::get('/admin/payouts', [\App\Http\Controllers\Finance\PayoutController::class, 'index']);
    Route::post('/admin/payouts', [\App\Http\Controllers\Finance\PayoutController::class, 'request']);
    Route::post('/admin/orders/{order}/refunds', [\App\Http\Controllers\Finance\FinancialAdminController::class, 'refund']);
    Route::get('/admin/finance/taxes', [\App\Http\Controllers\Finance\FinancialAdminController::class, 'taxes']);
    Route::get('/admin/finance/tax-report', [\App\Http\Controllers\Finance\FinancialAdminController::class, 'taxReport']);
    Route::post('/admin/finance/taxes', [\App\Http\Controllers\Finance\FinancialAdminController::class, 'storeTax']);
    Route::get('/admin/orders', [\App\Http\Controllers\Finance\FinanceController::class, 'adminOrders']);
    Route::get('/admin/orders/{order:public_id}/invoice', [\App\Http\Controllers\Finance\FinanceController::class, 'adminInvoice']);
    Route::post('/downloads/{entitlement}', [\App\Http\Controllers\Public\CheckoutController::class, 'download']);
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index'])->middleware('admin');
        Route::get('/agents', [\App\Http\Controllers\Admin\AgentController::class, 'index']);
        Route::get('/agent-roles', [\App\Http\Controllers\Admin\AgentController::class, 'roles']);
        Route::post('/agent-roles', [\App\Http\Controllers\Admin\AgentController::class, 'storeRole']);
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
        Route::get('/review-updates', [\App\Http\Controllers\Admin\ProductReviewController::class, 'updateQueue']);
        Route::post('/product-updates/{version}/review', [\App\Http\Controllers\Admin\ProductReviewController::class, 'decideUpdate']);
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
Route::get('products/{product}/reviews/eligibility',[\App\Http\Controllers\Public\CustomerProductReviewController::class,'eligibility'])->middleware('auth:sanctum');
Route::get('products/{product:slug}/reviews',[\App\Http\Controllers\Public\CustomerProductReviewController::class,'index']);
Route::post('products/{product}/reviews',[\App\Http\Controllers\Public\CustomerProductReviewController::class,'store'])->middleware('auth:sanctum');
Route::get('products/{product:slug}/comments',[\App\Http\Controllers\Public\ProductCommentController::class,'index']);
Route::post('products/{product}/comments',[\App\Http\Controllers\Public\ProductCommentController::class,'store'])->middleware('auth:sanctum');
Route::post('products/{product}/feedback/replies',[\App\Http\Controllers\Public\ProductFeedbackController::class,'reply'])->middleware('auth:sanctum');
Route::post('products/{product}/feedback/reports',[\App\Http\Controllers\Public\ProductFeedbackController::class,'report'])->middleware('auth:sanctum');
Route::delete('admin/feedback/{type}/{id}',[\App\Http\Controllers\Admin\ProductFeedbackController::class,'destroy'])->middleware('auth:sanctum');
Route::get('authors/{author}/portfolio',[PortofolioController::class,'publicPortfolio']);

Route::post('admin/products/{product}/review', [\App\Http\Controllers\Admin\ProductReviewController::class, 'decide'])->middleware('auth:sanctum');

Route::get('testing', function () {
    return response()->json("LFKdslkfj");
});
