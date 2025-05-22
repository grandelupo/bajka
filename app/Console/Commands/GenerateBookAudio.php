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
        {--end-page= : End at this page number}';

    protected $description = 'Generate audio for a book using ElevenLabs';

    private $elevenLabsApiKey;
    private $voiceId;

    public function handle()
    {
        $this->elevenLabsApiKey = $this->option('api-key') ?? config('services.elevenlabs.api_key');
        $this->voiceId = $this->option('voice-id') ?? config('services.elevenlabs.voice_id');

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
            if (empty($page->audio_url)) {
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
        $response = Http::withHeaders([
            'xi-api-key' => $this->elevenLabsApiKey,
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$this->voiceId}", [
            'text' => $text,
            'model_id' => 'eleven_monolingual_v1',
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

    private function getAudioDuration(string $audioUrl): int
    {
        // You might want to use a library like getID3 to get the actual duration
        // For now, we'll estimate based on word count
        $words = str_word_count(file_get_contents(public_path($audioUrl)));
        return ceil($words * 0.3); // Assuming 0.3 seconds per word
    }
} 