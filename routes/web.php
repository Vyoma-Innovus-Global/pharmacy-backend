<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
// use App\Http\Controllers\TestController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/payment-check', [PaymentController::class, 'check_payment']);
Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/uploads/{path}', function (string $path) {
    if (str_contains($path, '..') || str_starts_with($path, '/')) {
        abort(404);
    }

    $uploadRoot = realpath(storage_path('app/public/uploads'));
    $filePath = realpath(storage_path("app/public/uploads/{$path}"));

    if (!$uploadRoot || !$filePath || !str_starts_with($filePath, $uploadRoot) || !is_file($filePath)) {
        abort(404);
    }

    return response()->file($filePath);
})->where('path', '.*');

Route::prefix('payment')->group(function () {
    Route::post('/success', [PaymentController::class, 'institutePaymentSuccess']);
    Route::post('/fail', [PaymentController::class, 'institutePaymentFail']);
    Route::post('/push', [PaymentController::class, 'paymentPush']);
    Route::get('/receipt/{order_id}', [PaymentController::class, 'downloadInstitutePaymentReceipt']);
});

Route::prefix('student-payment')->group(function () {
    Route::get('/submit', [PaymentController::class, 'studentPaymentSubmit']);
    Route::match(['get', 'post'], '/success', [PaymentController::class, 'studentPaymentSuccess']);
    Route::match(['get', 'post'], '/fail', [PaymentController::class, 'studentPaymentFail']);
});

Route::get('/paynow',[PaymentController::class, 'payment']);
Route::get('/clear-all', function() {
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return 'All caches cleared!';
});
// Route::post('/packing-slip-list', [TestController::class, 'packingSlipList']);

Route::get('/get-password', function () {
    $password = request()->get('password');

    if (!$password) {
        return response()->json(['error' => 'No password provided'], 400);
    }

    return response()->json([
        'original' => $password,
        'sha512'   => hash('sha512', $password),
    ]);
});
