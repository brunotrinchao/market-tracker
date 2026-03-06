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

Route::prefix('/shared-shopping-lists/{token}')
    ->middleware('throttle:90,1')
    ->group(function (): void {
        Route::get('/', [SharedShoppingListController::class, 'show'])
            ->name('shared-shopping-lists.show');
        Route::post('/items', [SharedShoppingListController::class, 'storeItem'])
            ->name('shared-shopping-lists.items.store');
        Route::post('/items/reorder', [SharedShoppingListController::class, 'reorderItems'])
            ->name('shared-shopping-lists.items.reorder');
        Route::post('/items/{item}/remove', [SharedShoppingListController::class, 'removeItem'])
            ->name('shared-shopping-lists.items.remove');
        Route::get('/products/search', [SharedShoppingListController::class, 'searchProducts'])
            ->name('shared-shopping-lists.products.search');
        Route::get('/barcode/{barcode}', [SharedShoppingListController::class, 'lookupBarcode'])
            ->name('shared-shopping-lists.barcode.lookup');
    });

Route::middleware('auth')->group(function (): void {
    Route::post('/invoices/import-from-qr', InvoiceQrImportController::class)
        ->name('invoices.import.from-qr');
});
