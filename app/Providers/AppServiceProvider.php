<?php

namespace App\Providers;

use Broadcast;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login-attempt', function (Request $request) {
            $key = 'login-attempt:' . ($request->input('mobile') ?? $request->ip());

            return Limit::perMinutes(5, 5)->by($key); // 3 تلاش در هر 5 دقیقه
        });


        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
