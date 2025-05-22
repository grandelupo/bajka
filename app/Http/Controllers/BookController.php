<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(): Response
    {
        $books = Book::with(['pages' => function ($query) {
            $query->orderBy('page_number');
        }])->get();

        return Inertia::render('Books/Index', [
            'books' => $books,
        ]);
    }

    public function show(Book $book): Response
    {
        return Inertia::render('Books/Show', [
            'book' => $book,
            'page' => $book->pages->first()
        ]);
    }
} 