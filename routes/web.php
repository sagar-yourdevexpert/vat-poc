<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZatcaController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/zatca-test-standalone', [ZatcaController::class, 'testStandalone']);
// ZATCA VAT Integration APIs
Route::post('/api/zatca/generate-invoice', [ZatcaController::class, 'generateInvoice']);
Route::post('/api/zatca/sign-invoice', [ZatcaController::class, 'signInvoice']);
Route::post('/api/zatca/generate-csr', [ZatcaController::class, 'generateCsr']);
Route::post('/api/zatca/report-invoice', [ZatcaController::class, 'reportInvoiceToZatca']);
