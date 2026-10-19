<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CoinPurchaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Book routes
    Route::get('/books', [BookController::class, 'index'])->name('books.index');

    // Coin purchase routes
    Route::get('/coins/purchase', [CoinPurchaseController::class, 'index'])->name('coins.purchase');
    Route::get('/coins/purchase/confirm', [CoinPurchaseController::class, 'confirm'])->name('coins.purchase.confirm');
    Route::get('/coins/history', [CoinPurchaseController::class, 'history'])->name('coins.history');
    Route::post('/transactions/create-payment-intent', [CoinPurchaseController::class, 'createPaymentIntent'])->name('transactions.create-payment-intent');
    Route::post('/transactions/purchase-page', [CoinPurchaseController::class, 'purchasePage'])->name('transactions.purchase-page');
});

// Public book routes
// Retired editions (see config/books.php) send old links to the same page of the current one.
foreach (config('books.replaced', []) as $old => $current) {
    Route::get("/books/{$old}/pages/{page}", fn (int $page) => redirect()->route('pages.show', ['book' => $current, 'page' => $page]))
        ->where('page', '[0-9]+');
    Route::get("/books/{$old}", fn () => redirect()->route('pages.show', ['book' => $current, 'page' => 1]));
}

Route::get('/books/{book}', function ($book) {
    return redirect()->route('pages.show', ['book' => $book, 'page' => 1]);
})->name('books.show');
Route::get('/books/{book}/pages/{page}', [PageController::class, 'show'])
    ->where('page', '[0-9]+')
    ->name('pages.show')
    ->scopeBindings();

// Google OAuth routes
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
