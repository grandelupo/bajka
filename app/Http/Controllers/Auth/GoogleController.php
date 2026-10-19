<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate(
                ['google_id' => $googleUser->id],
                [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'avatar' => $googleUser->avatar,
                    'password' => bcrypt(Str::random(32)),
                ]
            );

            Auth::login($user);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google authentication failed');
        }
    }
} 