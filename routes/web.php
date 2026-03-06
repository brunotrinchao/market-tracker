<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\InvoiceQrImportController;
use App\Http\Controllers\Public\SharedShoppingListController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::get('/shared-shopping-lists/{token}', [SharedShoppingListController::class, 'show'])
    ->name('shared-shopping-lists.show');
Route::post('/shared-shopping-lists/{token}/items', [SharedShoppingListController::class, 'storeItem'])
    ->name('shared-shopping-lists.items.store');
Route::get('/shared-shopping-lists/{token}/calendar.ics', [SharedShoppingListController::class, 'appleCalendarIcs'])
    ->name('shared-shopping-lists.calendar.ics');

Route::middleware('auth')->group(function (): void {
    Route::post('/invoices/import-from-qr', InvoiceQrImportController::class)
        ->name('invoices.import.from-qr');
});
