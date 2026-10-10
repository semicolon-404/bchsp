<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\SitePageController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');

// Pages of text written in the panel (System > Pages).
Route::get('/p/{page}', [SitePageController::class, 'show'])->name('pages.show');

// The language switch (components/site/language-switch). Remembered for a year; read by the SetLocale middleware.
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(isset(SetLocale::LANGUAGES[$locale]), 404);

    return back(fallback: route('home'))->withCookie(cookie(SetLocale::COOKIE, $locale, 60 * 24 * 365));
})->name('language');
