<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Http\Middleware\RequireTwoStepOfStaff;
use App\Support\SiteSettings;
use App\Support\Theme;
use App\Support\TwoStep;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // A username or an email address, then the password (App\Filament\Auth\Login).
            ->login(Login::class)
            // Name, email, password, and two-step sign-in: a code from an authenticator app after the password.
            // The panel is told "required" so the set-up page exists; RequireTwoStepOfStaff asks on each request whether it is.
            ->profile(isSimple: false)
            ->multiFactorAuthentication(fn () => [TwoStep::provider()], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireTwoStepOfStaff::class)
            ->brandName(fn () => config('app.name'))
            // The hospital's logo beside the name when it has one, and the same picture in the browser's tab as the site has.
            ->brandLogo(fn () => SiteSettings::logoUrl())
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => url('/icon-192.png'))
            // The site opens light unless the person chose otherwise; both share the same saved choice.
            ->defaultThemeMode(ThemeMode::Light)
            // Each group is found by its English name (what the resources carry) and shown in the reader's language.
            ->navigationGroups(collect(['Announcements', 'ITA', 'Blog', 'Hospital', 'System'])
                ->mapWithKeys(fn (string $group) => [$group => NavigationGroup::make(fn () => __($group))])
                ->all())
            // The same colour as the site: its own green, or the one an administrator gave it (App\Support\Theme).
            ->colors(fn () => ['primary' => Color::hex(Theme::accent())])
            // In mourning the panel is black and white with the site.
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Theme::isMourning() ? new HtmlString('<style>html{filter:grayscale(1)}</style>') : '')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
