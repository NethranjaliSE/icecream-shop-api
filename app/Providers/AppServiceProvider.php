<?php

namespace App\Providers;

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
    }

    /**
     * Personal access tokens are what POST /api/auth/login hands out, so their
     * lifetime is the session length for API clients.
     */
    protected function configurePassport(): void
    {
        Passport::personalAccessTokensExpireIn(
            now()->addHours(config('auth.token_ttl_hours'))
        );
    }
}
