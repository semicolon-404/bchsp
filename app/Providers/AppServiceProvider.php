<?php

namespace App\Providers;

use App\Support\SiteSettings;
use App\Support\StaffLog;
use App\Support\Wording;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Phrases an administrator rewrote in the panel win over the language files (App\Support\Wording).
        $this->app->extend('translator', function (Translator $translator) {
            $wording = new Wording($translator->getLoader(), $translator->getLocale());
            $wording->setFallback($translator->getFallback());

            return $wording;
        });
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

        // The box a file is dropped on says so in the reader's language: the uploader itself has no Thai.
        FileUpload::configureUsing(fn (FileUpload $upload) => $upload->placeholder(fn () => __('Drop a file here, or :browse', ['browse' => '<span class="filepond--label-action">'.e(__('choose one')).'</span>'])));
    }
}
