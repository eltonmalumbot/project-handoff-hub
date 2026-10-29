<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.github.client_id') || ! config('services.github.client_secret')) {
            return redirect()->route('login')->withErrors(['oauth' => 'Login GitHub belum dikonfigurasi. Hubungi pemilik aplikasi.']);
        }

        return Socialite::driver('github')->setScopes([])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors(['oauth' => 'Otorisasi GitHub dibatalkan.']);
        }
        try {
            $identity = Socialite::driver('github')->setScopes([])->user();
        } catch (InvalidStateException|GuzzleException|ConnectionException $exception) {
            return redirect()->route('login')->withErrors(['oauth' => 'Login GitHub gagal atau sesi kedaluwarsa. Silakan coba lagi.']);
        }
        $id = (string) $identity->getId();
        if (! in_array($id, config('handoff.allowed_github_ids'), true)) {
            return redirect()->route('login')->withErrors(['oauth' => 'Akun GitHub ini tidak diizinkan. Aplikasi hanya untuk pemilik.']);
        }
        $user = User::updateOrCreate(['github_id' => $id], ['name' => $identity->getName() ?: $identity->getNickname() ?: 'Pemilik', 'github_login' => $identity->getNickname() ?: $id]);
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
