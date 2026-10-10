<?php

namespace App\Providers;

use App\Support\SiteSettings;
use App\Support\StaffLog;
use Illuminate\Support\ServiceProvider;

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
        // What an administrator changed on the panel's "Site settings" page replaces the config values of the same name.
        SiteSettings::apply();

        // Sign-ins, and what staff change in the panel, go to the access log.
        StaffLog::listen();
    }
}
