<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookImport extends Command
{
    protected $signature = 'book:import 
                            {file : The JSON file to import from}
                            {--force : Force import even if book exists}
                            {--advertise : Set this book as the advertised book}';

    protected $description = 'Import a book from JSON format';

    public function handle()
    {
        $file = $this->argument('file');
        $force = $this->option('force');
        $advertise = $this->option('advertise');

        if (!Storage::exists($file)) {
            $this->error("File {$file} not found.");
            return 1;
        }

        $json = Storage::get($file);
        $data = json_decode($json, true);

        if (!$data || !isset($data['book']) || !isset($data['pages'])) {
            $this->error('Invalid JSON format.');
            return 1;
        }

        try {
            DB::beginTransaction();

            // Check if book exists
            $existingBook = Book::find($data['book']['id']);
            if ($existingBook && !$force) {
                if (!$this->confirm("Book with ID {$data['book']['id']} already exists. Do you want to overwrite it?")) {
                    $this->info('Import cancelled.');
                    return 0;
                }
            }

            // Create or update the book
            $book = Book::updateOrCreate(
                ['id' => $data['book']['id']],
                [
                    'title' => $data['book']['title'],
                    'description' => $data['book']['description'],
                    'price_per_page' => $data['book']['price_per_page'],
                    'cover_image' => $data['book']['cover_image'] ?? 'https://picsum.photos/800/1200',
                    'total_pages' => count($data['pages']),
                ]
            );

            // Delete existing pages
            $book->pages()->delete();

            // Create new pages
            foreach ($data['pages'] as $pageData) {
                Page::create([
                    'book_id' => $book->id,
                    'page_number' => $pageData['page_number'],
                    'content' => $pageData['content'],
                    'video_url' => $pageData['video_url'],
                ]);
            }

            // Update ADVERTISED_BOOK_ID in .env if requested
            if ($advertise) {
                $this->updateAdvertisedBookId($book->id);
            }

            DB::commit();
            $this->info("Book imported successfully. ID: {$book->id}");
            if ($advertise) {
                $this->info("Book set as advertised book.");
            }
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error importing book: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Update the ADVERTISED_BOOK_ID in the .env file
     *
     * @param int $bookId
     * @return void
     */
    protected function updateAdvertisedBookId(int $bookId): void
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            $this->error('.env file not found.');
            return;
        }

        $envContent = file_get_contents($envFile);
        $pattern = '/^ADVERTISED_BOOK_ID=.*/m';
        $replacement = "ADVERTISED_BOOK_ID={$bookId}";

        if (preg_match($pattern, $envContent)) {
            // Replace existing ADVERTISED_BOOK_ID
            $newContent = preg_replace($pattern, $replacement, $envContent);
        } else {
            // Add new ADVERTISED_BOOK_ID at the end of the file
            $newContent = $envContent . "\n" . $replacement;
        }

        if (file_put_contents($envFile, $newContent) === false) {
            $this->error('Failed to update .env file.');
            return;
        }

        // Clear config cache to ensure new value is picked up
        $this->call('config:clear');
    }
}