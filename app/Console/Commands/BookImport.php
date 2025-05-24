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
                            {--force : Force import even if book exists}';

    protected $description = 'Import a book from JSON format';

    public function handle()
    {
        $file = $this->argument('file');
        $force = $this->option('force');

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

            DB::commit();
            $this->info("Book imported successfully. ID: {$book->id}");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error importing book: {$e->getMessage()}");
            return 1;
        }
    }
}