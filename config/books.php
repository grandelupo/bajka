<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Book Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for books in the application.
    |
    */

    'advertised_book_id' => env('ADVERTISED_BOOK_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Retired editions
    |--------------------------------------------------------------------------
    |
    | Editions that were removed; links to them land on the same page of the
    | edition that replaced them: old id => current id.
    |
    | 5 was the first draft (59 pages, no chapters); 8 was the edited text of 9
    | without the narration. Both were deleted on 2026-10-19 after their
    | purchases were moved to (or refunded against) book 9.
    |
    */

    'replaced' => [
        5 => 9,
        8 => 9,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soundscapes
    |--------------------------------------------------------------------------
    |
    | Which ambience plays behind each page when the reader turns on "Klimat":
    | book id => [page range => scene]. Scenes are the loops in
    | public/sounds/ambience. A page can also name its own scene with a
    | [scene]rain[/scene] tag in its content, which wins over this list.
    |
    */

    'scenes' => [
        // "To już są inne czasy"
        9 => [
            '1' => 'magic',          // once upon a time…
            '2-12' => 'home',        // Olga's room, the dusting, the drone workshop
            '13-14' => 'wind',       // the dragon on the fort at sunset
            '15' => 'night',         // falling asleep on the moss
            '16-21' => 'meadow',     // morning meadow, breakfast with Bronka
            '22-25' => 'woods',      // sneaking through the bushes to the fort
            '26-28' => 'fireplace',  // the old field kitchen
            '29-31' => 'home',       // the house without Kukiełka
            '32' => 'nightcity',     // street lamps, Olga at the window
            '33-39' => 'city',       // Kocica w Szpilkach in town
            '40-45' => 'park',
            '46-49' => 'magic',      // the enchanted city
            '50-52' => 'mall',
            '53' => 'cafe',          // "U Szalonego Lwa"
            '54' => 'park',
            '55' => 'home',
            '56' => 'city',
            '57-58' => 'rain',       // drizzle at the fort, chess under the roof
            '59' => 'woods',
            '60' => 'night',         // the old orangery
            '61' => 'fireplace',     // the soft plaid
            '62' => 'magic',
        ],
    ],
];