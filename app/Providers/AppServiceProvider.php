<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAllowedOwner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
        }
        RateLimiter::for('oauth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('github-sync', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));
        Livewire::addPersistentMiddleware([EnsureAllowedOwner::class]);
    }
}
