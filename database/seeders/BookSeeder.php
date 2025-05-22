<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Page;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        // Create the book
        $book = Book::create([
            'title' => 'The Adventures of Lorem Ipsum',
            'description' => 'A whimsical journey through the world of Lorem Ipsum, where words come alive and stories unfold in unexpected ways.',
            'cover_image' => 'https://picsum.photos/800/1200',
            'total_pages' => 10,
            'price_per_page' => 5,
        ]);

        // Create pages with Lorem Ipsum content
        $loremIpsum = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
            'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
            'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
            'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.',
            'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores.',
            'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti.',
            'Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates.',
            'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat.',
            'Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur.',
        ];

        foreach ($loremIpsum as $index => $content) {
            Page::create([
                'book_id' => $book->id,
                'page_number' => $index + 1,
                'content' => $content,
                'image_url' => "https://picsum.photos/800/1200?random={$index}",
                'video_url' => $index % 3 === 0 ? 'https://example.com/sample-video.mp4' : null, // Add video every 3rd page
                'audio_url' => $index % 2 === 0 ? 'https://example.com/sample-audio.mp3' : null, // Add audio every 2nd page
                'audio_duration' => $index % 2 === 0 ? 120 : null, // 2 minutes for pages with audio
            ]);
        }
    }
} 