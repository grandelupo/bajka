<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BookExport extends Command
{
    protected $signature = 'book:export 
                            {book_id : The ID of the book to export}
                            {--file= : The JSON file to export to (optional)}';

    protected $description = 'Export a book to JSON format';

    public function handle()
    {
        $bookId = $this->argument('book_id');
        $file = $this->option('file');

        $book = Book::with(['pages' => function ($query) {
            $query->orderBy('page_number');
        }])->find($bookId);

        if (!$book) {
            $this->error("Book with ID {$bookId} not found.");
            return 1;
        }

        $exportData = [
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'description' => $book->description,
                'price_per_page' => $book->price_per_page,
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at,
            ],
            'pages' => $book->pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'page_number' => $page->page_number,
                    'content' => $page->content,
                    'video_url' => $page->video_url,
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at,
                ];
            })->toArray(),
        ];

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($file) {
            Storage::put($file, $json);
            $this->info("Book exported to {$file}");
        } else {
            $this->line($json);
        }

        return 0;
    }
}