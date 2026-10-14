<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItaController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SitePageController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
Route::post('/announcements/{announcement}/helpful', [AnnouncementController::class, 'helpful'])->middleware('throttle:12,1')->name('announcements.helpful');

// Articles are announcements now: an address shared before still leads somewhere.
Route::get('/blog/{slug?}', [AnnouncementController::class, 'fromBlog']);

Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');

Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::view('/phones', 'phones')->name('phones');
Route::view('/faq', 'faq')->name('faq');
Route::view('/services', 'services')->name('services');
Route::get('/calendar', [AnnouncementController::class, 'calendar'])->name('calendar');
Route::get('/posts', [AnnouncementController::class, 'posts'])->name('posts');
Route::view('/updates', 'updates')->name('updates');
Route::view('/schedule', 'schedule')->name('schedule');
Route::view('/downloads', 'downloads')->name('downloads');
// The page of posters is the list of announcements shown as cards: an address shared before still leads there.
Route::redirect('/posters', '/announcements?view=cards', 301);
Route::get('/ita/{year?}', [ItaController::class, 'show'])->whereNumber('year')->name('ita');

// Everything on the site in one list (the dock's "Menu" tab), and the page of services for staff.
Route::view('/menu', 'menu')->name('menu');
Route::view('/staff', 'staff')->name('staff');

// Pages of text written in the panel (System > Pages).
Route::get('/p/{page}', [SitePageController::class, 'show'])->name('pages.show');

Route::get('/search', SearchController::class)->name('search');
// Complaints, suggestions and praise: how it works, the form, the page that gives the tracking number, and
// the page a matter is followed on (App\Http\Controllers\ComplaintController). Sending and looking up are
// limited for one address, so the form is not flooded and codes are not guessed at.
Route::view('/complaint', 'complaint.index')->name('complaint');
Route::get('/complaint/send', [ComplaintController::class, 'create'])->name('complaint.create');
Route::post('/complaint/send', [ComplaintController::class, 'store'])->middleware('throttle:8,10')->name('complaint.store');
Route::get('/complaint/sent', [ComplaintController::class, 'sent'])->name('complaint.sent');
Route::get('/complaint/track', [ComplaintController::class, 'track'])->name('complaint.track');
Route::post('/complaint/track', [ComplaintController::class, 'lookup'])->middleware('throttle:20,10')->name('complaint.lookup');
// What a sender attached, for an administrator who is signed in (checked in the controller).
Route::get('/admin/complaint-files/{complaint}/{index}', [ComplaintController::class, 'file'])->whereNumber('index')->name('complaint.file');
// A QR code leading to a page of this site, for posters and signs (App\Http\Controllers\QrController).
Route::get('/qr.svg', QrController::class)->middleware('throttle:60,1')->name('qr');

// The report for executives, to print or to open in Excel: for administrators (App\Http\Controllers\ReportController).
Route::get('/admin/reports-print', [ReportController::class, 'print'])->name('reports.print');
Route::get('/admin/reports-download', [ReportController::class, 'download'])->name('reports.download');

// For search engines (App\Http\Controllers\SeoController).
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/feed', [SeoController::class, 'feed'])->name('feed');

// For a phone that keeps the site on its home screen: what it is called and looks like, its picture,
// the small program that answers when there is no connection, and the page it answers with.
Route::get('/manifest.webmanifest', [SeoController::class, 'manifest'])->name('manifest');
Route::get('/icon-{size}.png', [SeoController::class, 'icon'])->whereNumber('size')->name('icon');
Route::get('/sw.js', [SeoController::class, 'worker'])->name('worker');
Route::view('/offline', 'offline')->name('offline');

// The language switch (components/site/language-switch). Remembered for a year; read by the SetLocale middleware.
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(isset(SetLocale::LANGUAGES[$locale]), 404);

    return back(fallback: route('home'))->withCookie(cookie(SetLocale::COOKIE, $locale, 60 * 24 * 365));
})->name('language');
