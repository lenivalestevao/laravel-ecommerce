<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/category/{category}', [ShopController::class, 'index'])->name('shop.category');
Route::get('/product/{product}', [ShopController::class, 'show'])->name('shop.show');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');

    // Coupon routes come before /{product}, otherwise DELETE /cart/coupon would match a product slug.
    Route::post('/coupon', [CartController::class, 'applyCoupon'])->name('coupon.apply')->middleware('throttle:10,1');
    Route::delete('/coupon', [CartController::class, 'removeCoupon'])->name('coupon.remove');

    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::patch('/{product}', [CartController::class, 'update'])->name('update');
    Route::delete('/{product}', [CartController::class, 'remove'])->name('remove');
});

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:20,1');

Route::get('/orders/{order}', [CheckoutController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/pay', [CheckoutController::class, 'pay'])->name('orders.pay');

Route::get('/track', [TrackController::class, 'form'])->name('track');
Route::post('/track', [TrackController::class, 'lookup'])->name('track.lookup')->middleware('throttle:10,1');

Route::get('/payment/{order}/return', [PaymentController::class, 'return'])->name('payment.return');
Route::get('/payment/{order}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::post('/payment/webhook/{gateway}', [PaymentController::class, 'webhook'])->name('payment.webhook');

/*
|--------------------------------------------------------------------------
| Customer accounts
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('coupons', Admin\CouponController::class)->except('show');

    Route::get('orders/export', [Admin\OrderController::class, 'export'])->name('orders.export');
    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::post('orders/{order}/paid', [Admin\OrderController::class, 'markPaid'])->name('orders.paid');

    Route::get('customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
});
