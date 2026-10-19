<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Support\BookSummary;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $book = Book::find(config('books.advertised_book_id')) ?? Book::query()->first();

        return Inertia::render('Home', [
            'book' => $book ? BookSummary::make($book) : null,
        ]);
    }
}
