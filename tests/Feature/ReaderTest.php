<?php

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeBook(int $pages = 16): Book
{
    $book = Book::create([
        'title' => 'Bajka testowa',
        'description' => 'Opis',
        'cover_image' => '',
        'total_pages' => $pages,
        'price_per_page' => 5,
    ]);

    for ($n = 1; $n <= $pages; $n++) {
        $book->pages()->create([
            'page_number' => $n,
            'content' => ($n === 13 ? '[chapter]Rozdział drugi[/chapter]' : '')."Treść strony {$n}[video]{$n}[/video]",
            'image_url' => '',
            'audio_url' => "/storage/audio/{$n}.mp3",
        ]);
    }

    return $book;
}

function pageProp($response, int $n): array
{
    return collect($response->viewData('page')['props']['pages'])->firstWhere('page_number', $n);
}

it('hides the text and narration of locked pages from guests', function () {
    $book = makeBook();

    $response = $this->get("/books/{$book->id}/pages/1")->assertOk();

    expect(pageProp($response, 6))->toMatchArray(['locked' => null, 'content' => 'Treść strony 6'])
        ->and(pageProp($response, 7))->toMatchArray(['locked' => 'login', 'content' => null, 'audio_url' => null])
        ->and(pageProp($response, 13)['chapter'])->toBe('Rozdział drugi');
});

it('shows a locked page instead of redirecting or charging when it is opened directly', function () {
    $book = makeBook();
    $user = User::factory()->create(['coins' => 100]);

    $response = $this->actingAs($user)->get("/books/{$book->id}/pages/13")->assertOk();

    expect(pageProp($response, 11)['locked'])->toBeNull()
        ->and(pageProp($response, 13)['locked'])->toBe('coins')
        ->and($user->fresh()->coins)->toBe(100);
});

it('charges only for pages not already owned', function () {
    $book = makeBook();
    $user = User::factory()->create(['coins' => 100]);

    $this->actingAs($user)
        ->postJson('/transactions/purchase-page', ['book_id' => $book->id, 'page_numbers' => [12, 13]])
        ->assertOk()
        ->assertJsonPath('spent', 10)
        ->assertJsonPath('pages.0.content', 'Treść strony 12');

    $this->actingAs($user)
        ->postJson('/transactions/purchase-page', ['book_id' => $book->id, 'page_numbers' => [13, 14]])
        ->assertOk()
        ->assertJsonPath('spent', 5)
        ->assertJsonPath('remaining_coins', 85);

    expect($user->fresh()->coins)->toBe(85);
});

it('does not charge for free pages and refuses when coins run short', function () {
    $book = makeBook();
    $user = User::factory()->create(['coins' => 4]);

    $this->actingAs($user)
        ->postJson('/transactions/purchase-page', ['book_id' => $book->id, 'page_numbers' => [10, 11]])
        ->assertOk()
        ->assertJsonPath('spent', 0);

    $this->actingAs($user)
        ->postJson('/transactions/purchase-page', ['book_id' => $book->id, 'page_numbers' => [12]])
        ->assertStatus(402);

    expect($user->fresh()->coins)->toBe(4);
});

it('sends readers back to their page after signing in', function () {
    $book = makeBook();
    $user = User::factory()->create();

    $this->get("/login?return=/books/{$book->id}/pages/9")->assertOk();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect("/books/{$book->id}/pages/9");
});

it('ignores return addresses that leave the site', function () {
    $user = User::factory()->create();

    $this->get('/login?return=//evil.example')->assertOk();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/dashboard');
});

it('sends links to a retired edition to the same page of its replacement', function () {
    $old = makeBook();
    $new = makeBook();
    config(["books.replaced.{$old->id}" => $new->id]);

    $this->get("/books/{$old->id}/pages/4")->assertRedirect("/books/{$new->id}/pages/4");
});

it('still redirects links to a deleted edition', function () {
    $this->get('/books/5/pages/7')->assertRedirect('/books/9/pages/7');
    $this->get('/books/8')->assertRedirect('/books/9/pages/1');
});
