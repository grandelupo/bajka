<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Events the browser cannot see on its own: a payment Stripe confirmed, a new
 * account. They are flashed to the session, shared with the next Inertia page
 * and sent to Google Analytics from there, so each one is reported once and
 * only when it really happened.
 */
class Analytics
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function report(string $name, array $params = []): void
    {
        Session::flash('analytics', ['name' => $name, 'params' => $params]);
    }
}
