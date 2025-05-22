<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Page;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(Book $book, Page $page): Response
    {
        $user = auth()->user();
        $freePages = 6;
        $registeredFreePages = 11;

        // Load all pages for the book
        $book->load(['pages' => function ($query) {
            $query->orderBy('page_number');
        }]);

        // Process video URLs to use local storage
        $book->pages->each(function ($page) {
            if ($page->video_url) {
                // Extract filename from the video URL
                $filename = basename($page->video_url);
                // Update to use local storage path
                $page->video_url = "/storage/videos/{$filename}";
            }
        });

        // Get purchased page IDs
        $purchasedPageIds = $user ? $user->transactions()
            ->where('book_id', $book->id)
            ->pluck('page_id')
            ->toArray() : [];

        // Check access for pages beyond free pages
        if ($page->page_number > $freePages) {
            if (!$user) {
                return redirect()->route('login');
            }

            if ($page->page_number > $registeredFreePages) {
                $hasPurchased = $user->transactions()
                    ->where('book_id', $book->id)
                    ->where('page_id', $page->id)
                    ->exists();

                if (!$hasPurchased) {
                    if ($user->coins < $book->price_per_page) {
                        return redirect()->route('coins.purchase')
                            ->with('error', 'Not enough coins to view this page');
                    }

                    // Create transaction and deduct coins
                    Transaction::create([
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                        'page_id' => $page->id,
                        'coins_spent' => $book->price_per_page,
                    ]);

                    $user->decrement('coins', $book->price_per_page);
                }
            }
        }

        return Inertia::render('Books/Show', [
            'book' => $book,
            'currentPageNumber' => $page->page_number,
            'accessConfig' => [
                'freePages' => $freePages,
                'registeredFreePages' => $registeredFreePages,
                'purchasedPageIds' => $purchasedPageIds,
                'isAuthenticated' => (bool)$user,
            ],
            'userCoins' => $user ? $user->coins : 0,
        ]);
    }
} 