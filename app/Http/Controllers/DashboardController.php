<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $books = Book::with(['pages' => function ($query) {
            $query->orderBy('page_number');
        }])
        ->latest()
        ->get();

        $recentTransactions = Transaction::with(['book', 'page'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $recentCoinPurchases = $user->coinPurchases()
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'books' => $books,
            'recentTransactions' => $recentTransactions,
            'recentCoinPurchases' => $recentCoinPurchases,
            'userCoins' => $user->coins,
        ]);
    }
} 