<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SitePageController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');

Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::view('/schedule', 'schedule')->name('schedule');
Route::view('/downloads', 'downloads')->name('downloads');
Route::view('/posters', 'posters')->name('posters');
Route::get('/ita/{year?}', [ItaController::class, 'show'])->whereNumber('year')->name('ita');

// Everything on the site in one list (the dock's "Menu" tab), and the page of services for staff.
Route::view('/menu', 'menu')->name('menu');
Route::view('/staff', 'staff')->name('staff');

// Pages of text written in the panel (System > Pages).
Route::get('/p/{page}', [SitePageController::class, 'show'])->name('pages.show');

Route::get('/search', SearchController::class)->name('search');

// The report for executives, to print or to open in Excel: for administrators (App\Http\Controllers\ReportController).
Route::get('/admin/reports-print', [ReportController::class, 'print'])->name('reports.print');
Route::get('/admin/reports-download', [ReportController::class, 'download'])->name('reports.download');

// For search engines (App\Http\Controllers\SeoController).
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// The language switch (components/site/language-switch). Remembered for a year; read by the SetLocale middleware.
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(isset(SetLocale::LANGUAGES[$locale]), 404);

    return back(fallback: route('home'))->withCookie(cookie(SetLocale::COOKIE, $locale, 60 * 24 * 365));
})->name('language');
