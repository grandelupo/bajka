<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Transaction;
use App\Support\BookSummary;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $books = Book::whereNotIn('id', array_keys(config('books.replaced', [])))->latest()->get()->map(fn (Book $book) => BookSummary::make($book, $user));

        $recentTransactions = Transaction::with(['book:id,title', 'page:id,page_number'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(6)
            ->get();

        return Inertia::render('Dashboard', [
            'books' => $books,
            'recentTransactions' => $recentTransactions,
            'recentCoinPurchases' => $user->coinPurchases()->latest()->take(6)->get(),
            'userCoins' => $user->coins,
            'stats' => [
                'pages_unlocked' => $user->transactions()->count(),
                'coins_spent' => (int) $user->transactions()->sum('coins_spent'),
                'member_since' => $user->created_at,
            ],
        ]);
    }
}
