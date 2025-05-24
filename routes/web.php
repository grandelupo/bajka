<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CoinPurchaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Home');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Book routes
    Route::get('/books', [BookController::class, 'index'])->name('books.index');

    // Coin purchase routes
    Route::get('/coins/purchase', [CoinPurchaseController::class, 'index'])->name('coins.purchase');
    Route::post('/coins/purchase', [CoinPurchaseController::class, 'store'])->name('coins.store');
    Route::get('/coins/history', [CoinPurchaseController::class, 'history'])->name('coins.history');
    Route::post('/transactions/create-payment-intent', [CoinPurchaseController::class, 'createPaymentIntent'])->name('transactions.create-payment-intent');
    Route::post('/transactions/purchase-page', [CoinPurchaseController::class, 'purchasePage'])->name('transactions.purchase-page');
});

// Public book routes
Route::get('/books/{book}', function ($book) {
    return redirect()->route('pages.show', ['book' => $book, 'page' => 1]);
})->name('books.show');
Route::get('/books/{book}/pages/{page}', [PageController::class, 'show'])->name('pages.show');

// Google OAuth routes
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

require __DIR__.'/auth.php';
