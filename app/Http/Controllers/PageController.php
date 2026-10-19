<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Page;
use App\Support\BookReader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * The reader. Opening a page never spends coins: a locked page is shown
     * locked, and the reader decides on the page itself whether to unlock it.
     */
    public function show(Book $book, Page $page): Response|RedirectResponse
    {
        if ($current = config("books.replaced.{$book->id}")) {
            return redirect()->route('pages.show', ['book' => $current, 'page' => $page->page_number]);
        }

        $user = auth()->user();
        $reader = new BookReader($book, $user);

        $pages = $book->pages()
            ->orderBy('page_number')
            ->get()
            ->map(fn (Page $p) => $reader->present($p))
            ->values();

        return Inertia::render('Books/Show', [
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'description' => $book->description,
                'total_pages' => $pages->count(),
                'price_per_page' => $book->price_per_page,
            ],
            'pages' => $pages,
            'currentPageNumber' => $page->page_number,
            'accessConfig' => [
                'freePages' => BookReader::FREE_PAGES,
                'registeredFreePages' => BookReader::REGISTERED_FREE_PAGES,
                'isAuthenticated' => (bool) $user,
            ],
            'userCoins' => $user?->coins ?? 0,
        ]);
    }
}
