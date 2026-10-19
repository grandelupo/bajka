<?php

namespace App\Support;

use App\Models\Book;
use App\Models\Page;
use App\Models\User;

/**
 * Decides who may read which page, and shapes pages for the reader.
 *
 * Locked pages travel without their text or narration, so the paywall holds
 * even for someone reading the page props in devtools.
 */
class BookReader
{
    public const FREE_PAGES = 6;

    public const REGISTERED_FREE_PAGES = 11;

    /** @var array<int, int> */
    private array $purchasedPageIds;

    public function __construct(private Book $book, private ?User $user)
    {
        $this->purchasedPageIds = $user
            ? $user->transactions()->where('book_id', $book->id)->pluck('page_id')->all()
            : [];
    }

    public function purchasedPageIds(): array
    {
        return $this->purchasedPageIds;
    }

    public function canRead(Page $page): bool
    {
        if ($page->page_number <= self::FREE_PAGES) {
            return true;
        }

        if (! $this->user) {
            return false;
        }

        return $page->page_number <= self::REGISTERED_FREE_PAGES
            || in_array($page->id, $this->purchasedPageIds, true);
    }

    /**
     * Why a page is locked: 'login' for guests, 'coins' for readers who have
     * to unlock it, null when it can be read.
     */
    public function lockReason(Page $page): ?string
    {
        if ($this->canRead($page)) {
            return null;
        }

        return $this->user ? 'coins' : 'login';
    }

    public function present(Page $page): array
    {
        $content = (string) $page->content;
        $media = self::media($content);
        $lock = $this->lockReason($page);

        return [
            'id' => $page->id,
            'page_number' => $page->page_number,
            'chapter' => self::chapter($content),
            'content' => $lock ? null : self::stripMediaTags($content),
            'audio_url' => $lock ? null : $page->audio_url,
            'image' => $media['image'],
            'video' => $lock ? null : $media['video'],
            'locked' => $lock,
            'scene' => $this->scene($page, $content),
        ];
    }

    /** The soundscape behind a page: its own [scene] tag, else the book's list. */
    private function scene(Page $page, string $content): ?string
    {
        if (preg_match('/\[scene\](.*?)\[\/scene\]/s', $content, $m)) {
            return trim($m[1]) ?: null;
        }

        foreach (config("books.scenes.{$this->book->id}", []) as $range => $scene) {
            [$from, $to] = array_pad(explode('-', (string) $range), 2, null);
            if ($page->page_number >= (int) $from && $page->page_number <= (int) ($to ?? $from)) {
                return $scene;
            }
        }

        return null;
    }

    public static function chapter(string $content): ?string
    {
        return preg_match('/\[chapter\](.*?)\[\/chapter\]/s', $content, $m) ? trim($m[1]) : null;
    }

    public static function stripMediaTags(string $content): string
    {
        return trim(preg_replace('/\[(video|scene)\].*?\[\/\1\]/s', '', $content));
    }

    /**
     * Pages name their illustration with [video]name[/video]; only some of
     * those files exist, so look before promising the browser anything.
     *
     * @return array{image: ?string, video: ?string}
     */
    public static function media(string $content): array
    {
        if (! preg_match('/\[video\](.*?)\[\/video\]/s', $content, $m)) {
            return ['image' => null, 'video' => null];
        }

        $name = basename(trim($m[1]));
        $root = storage_path('app/public');

        $image = null;
        foreach (['webp', 'png', 'jpg'] as $ext) {
            if (is_file("{$root}/images/{$name}.{$ext}")) {
                $image = "/images/{$name}.{$ext}";
                break;
            }
        }

        $video = is_file("{$root}/videos/{$name}.mp4") ? "/videos/{$name}.mp4" : null;

        return ['image' => $image, 'video' => $video];
    }
}
