<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Support\ApkPublish;

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
        Schema::defaultStringLength(191);

        // Share the newest published APK with guest-facing pages so the
        // "Get the App" links never go stale after a new release.
        View::composer(
            ['landing', 'auth.login', 'artist.login', 'artist.signup', 'artist.verify'],
            function ($view) {
                $view->with('apkUrl', ApkPublish::url());
            }
        );
    }
}
