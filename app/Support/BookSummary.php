<?php

namespace App\Support;

use App\Models\Book;
use App\Models\User;

/**
 * The shape of a book on the homepage, the dashboard and the bookshelf:
 * its chapters, its illustrations and, for a signed-in reader, how much of
 * it they own.
 */
class BookSummary
{
    public static function make(Book $book, ?User $user = null): array
    {
        $pages = $book->pages()->orderBy('page_number')->get(['id', 'page_number', 'content']);

        $chapters = [];
        $illustrations = [];

        foreach ($pages as $page) {
            $content = (string) $page->content;

            if ($title = BookReader::chapter($content)) {
                $chapters[] = ['title' => $title, 'page' => $page->page_number];
            }

            $media = BookReader::media($content);
            if ($media['image']) {
                $illustrations[] = ['image' => $media['image'], 'page' => $page->page_number];
            }
        }

        $owned = $user
            ? $user->transactions()->where('book_id', $book->id)->distinct()->count('page_id')
            : 0;

        return [
            'id' => $book->id,
            'title' => $book->title,
            'description' => $book->description,
            'total_pages' => $pages->count(),
            'price_per_page' => $book->price_per_page,
            'cover' => $illustrations[0]['image'] ?? null,
            'chapters' => $chapters,
            'illustrations' => $illustrations,
            'free_pages' => BookReader::FREE_PAGES,
            'registered_free_pages' => BookReader::REGISTERED_FREE_PAGES,
            'owned_pages' => $owned,
        ];
    }
}
