<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tokens are issued by our own /api/auth/login endpoint (personal access
        // tokens), so Passport's OAuth authorization-server routes aren't needed.
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePassport();
        $this->configureRateLimiting();
    }

    /**
     * Personal access tokens are what POST /api/auth/login hands out, so their
     * lifetime is the session length for API clients.
     */
    protected function configurePassport(): void
    {
        Passport::personalAccessTokensExpireIn(
            CarbonInterval::hours(config('auth.token_ttl_hours'))
        );
    }

    /**
     * Slow down password guessing: 5 login attempts per minute per email + IP.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });
    }
}
