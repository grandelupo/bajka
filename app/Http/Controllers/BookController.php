<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Support\BookSummary;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('Books/Index', [
            'books' => Book::whereNotIn('id', array_keys(config('books.replaced', [])))->latest()->get()->map(fn (Book $book) => BookSummary::make($book, $user)),
        ]);
    }
}
