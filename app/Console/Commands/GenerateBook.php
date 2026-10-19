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
        // Split text into paragraphs and chapters
        $paragraphs = preg_split('/\n/', $text);
        
        $currentChunk = '';
        $wordCount = 0;
        $maxWordsPerPage = $this->option('words-per-page');
        $isNewChapter = false;

        foreach ($paragraphs as $paragraph) {
            // Check if this is a chapter marker
            if (preg_match('/\[chapter\](.*?)\[\/chapter\]/', $paragraph, $matches)) {
                // If we have content in current chunk, save it first
                if ($currentChunk !== '') {
                    $this->textChunks[] = $currentChunk;
                    $currentChunk = '';
                    $wordCount = 0;
                }
                
                // Start new chunk with chapter title
                $currentChunk = $paragraph;
                $wordCount = str_word_count($paragraph);
                $isNewChapter = true;
                continue;
            }

            $paragraphWords = str_word_count($paragraph);
            
            // If adding this paragraph would exceed the limit and we're not at a chapter start
            if ($wordCount + $paragraphWords > $maxWordsPerPage && $currentChunk !== '' && !$isNewChapter) {
                $this->textChunks[] = $currentChunk;
                $currentChunk = $paragraph;
                $wordCount = $paragraphWords;
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
                $wordCount += $paragraphWords;
            }
            
            $isNewChapter = false;
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