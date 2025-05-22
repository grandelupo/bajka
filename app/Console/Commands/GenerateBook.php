<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Page;
use Illuminate\Console\Command;

class GenerateBook extends Command
{
    protected $signature = 'book:generate 
        {text_file : Path to the text file}
        {--title= : Book title}
        {--description= : Book description}
        {--cover= : Path to cover image}
        {--words-per-page=150 : Maximum words per page}';

    protected $description = 'Generate a book structure from text file';

    private $textChunks = [];

    public function handle()
    {
        // Read and process the text file
        $text = file_get_contents($this->argument('text_file'));
        $this->processText($text);

        // Create the book
        $book = $this->createBook();

        // Generate pages
        $this->generatePages($book);

        $this->info('Book structure generation completed successfully!');
        $this->info('Run "php artisan book:generate-audio {$book->id}" to generate audio for this book.');
        return 0;
    }

    private function processText(string $text)
    {
        // Split text into paragraphs
        $paragraphs = preg_split('/\n/', $text);
        
        $currentChunk = '';
        $wordCount = 0;
        $maxWordsPerPage = $this->option('words-per-page');

        foreach ($paragraphs as $paragraph) {
            $paragraphWords = str_word_count($paragraph);
            
            if ($wordCount + $paragraphWords > $maxWordsPerPage && $currentChunk !== '') {
                $this->textChunks[] = $currentChunk;
                $currentChunk = $paragraph;
                $wordCount = $paragraphWords;
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
                $wordCount += $paragraphWords;
            }
        }

        if ($currentChunk) {
            $this->textChunks[] = $currentChunk;
        }
    }

    private function createBook(): Book
    {
        return Book::create([
            'title' => $this->option('title') ?? 'Generated Book ' . now()->format('Y-m-d H:i:s'),
            'description' => $this->option('description') ?? 'A book generated from text',
            'cover_image' => $this->option('cover') ?? 'https://picsum.photos/800/1200',
            'total_pages' => count($this->textChunks),
            'price_per_page' => 5,
        ]);
    }

    private function generatePages(Book $book)
    {
        $bar = $this->output->createProgressBar(count($this->textChunks));
        $bar->start();

        foreach ($this->textChunks as $index => $content) {
            $pageNumber = $index + 1;
            
            // Create the page
            Page::create([
                'book_id' => $book->id,
                'page_number' => $pageNumber,
                'content' => $content,
                'image_url' => "https://picsum.photos/800/1200?random={$pageNumber}",
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
} 