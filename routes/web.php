<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\InvoiceQrImportController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/invoices/import-from-qr', InvoiceQrImportController::class)
        ->name('invoices.import.from-qr');
});
