<?php

use App\Http\Controllers\InvoiceQrImportController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware('auth')->group(function (): void {
    Route::post('/invoices/import-from-qr', InvoiceQrImportController::class)
        ->name('invoices.import.from-qr');
});
