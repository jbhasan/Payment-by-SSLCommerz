<?php

use Illuminate\Support\Facades\Route;
use Sayeed\PaymentBySslcommerz\Http\Controllers\SslCommerzPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// SSLCOMMERZ Start
Route::post('/pay', [SslCommerzPaymentController::class, 'index']);
Route::post('/payment-response/{response_type}', [SslCommerzPaymentController::class, 'paymentResponse']);
//SSLCOMMERZ END
