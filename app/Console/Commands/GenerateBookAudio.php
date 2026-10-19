<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateBookAudio extends Command
{
    protected $signature = 'book:generate-audio 
        {book_id : The ID of the book to generate audio for}
        {--voice-id= : ElevenLabs voice ID}
        {--api-key= : ElevenLabs API key}
        {--start-page=1 : Start from this page number}
        {--end-page= : End at this page number}
        {--force : Force regeneration of existing audio files}';

    protected $description = 'Generate audio for a book using ElevenLabs';

    private $elevenLabsApiKey;
    private $voiceId;

    public function handle()
    {
        $this->elevenLabsApiKey = $this->option('api-key') ?? config('services.elevenlabs.api_key');
        $this->voiceId = $this->option('voice-id') ?? config('services.elevenlabs.voice_id');
        $force = $this->option('force');

        if (!$this->elevenLabsApiKey || !$this->voiceId) {
            $this->error('ElevenLabs API key and voice ID are required');
            return 1;
        }

        $book = Book::findOrFail($this->argument('book_id'));
        $startPage = $this->option('start-page');
        $endPage = $this->option('end-page') ?? $book->total_pages;

        $pages = $book->pages()
            ->whereBetween('page_number', [$startPage, $endPage])
            ->orderBy('page_number')
            ->get();

        if ($pages->isEmpty()) {
            $this->error('No pages found in the specified range');
            return 1;
        }

        $bar = $this->output->createProgressBar($pages->count());
        $bar->start();

        foreach ($pages as $page) {
            if ($force || empty($page->audio_url)) {
                // Delete existing audio file if it exists
                if ($force && $page->audio_url) {
                    $this->deleteAudioFile($page->audio_url);
                }

                $audioUrl = $this->generateAudio($page->content);
                $page->update([
                    'audio_url' => $audioUrl,
                    'audio_duration' => $this->getAudioDuration($audioUrl),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Audio generation completed successfully!');
        return 0;
    }

    private function generateAudio(string $text): string
    {
        $cleanText = $this->cleanTextForAudio($text);
        
        $response = Http::withHeaders([
            'xi-api-key' => $this->elevenLabsApiKey,
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$this->voiceId}", [
            'text' => $cleanText,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ],
        ]);

        if (!$response->successful()) {
            $this->error("Failed to generate audio: " . $response->body());
            return '';
        }

        // Save the audio file
        $filename = Str::random(40) . '.mp3';
        Storage::disk('public')->put("audio/{$filename}", $response->body());

        return "/storage/audio/{$filename}";
    }

    private function cleanTextForAudio(string $text): string
    {
        // Remove chapter tags and their content
        $text = preg_replace('/\[chapter\].*?\[\/chapter\]/s', '', $text);
        
        // Remove video tags and their content
        $text = preg_replace('/\[video\].*?\[\/video\]/s', '', $text);
        
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Remove extra whitespace and normalize line endings
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    private function getAudioDuration(string $audioUrl): int
    {
        // You might want to use a library like getID3 to get the actual duration
        // For now, we'll estimate based on word count
        $words = str_word_count(file_get_contents(public_path($audioUrl)));
        return ceil($words * 0.3); // Assuming 0.3 seconds per word
    }

    private function deleteAudioFile(string $audioUrl): void
    {
        if (empty($audioUrl)) {
            return;
        }

        // Convert /storage/audio/filename.mp3 to audio/filename.mp3
        $path = str_replace('/storage/', '', $audioUrl);
        
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
} 