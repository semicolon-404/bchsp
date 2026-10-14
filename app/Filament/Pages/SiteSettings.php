<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\SiteSettingsForm;
use App\Filament\Concerns\TranslatesTitle;
use App\Support\Footer;
use App\Support\HomeLayout;
use App\Support\Menus;
use App\Support\SiteSettings as Settings;
use App\Support\StaffLog;
use App\Support\Theme;
use App\Support\Wording;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * What an administrator can change about the site without a developer.
 *
 * Field names use "__" where the setting has a ".", because a dot in a form
 * field's name means "nested" to Filament: "contact__phone" is the setting
 * "contact.phone" (see App\Support\SiteSettings).
 */
class SiteSettings extends Page
{
    use AdminOnly;
    use SiteSettingsForm;
    use TranslatesTitle;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $values = [];

        foreach ([...array_keys(Settings::CONFIG), ...Settings::CONTENT] as $key) {
            $values[self::field($key)] = Settings::get($key);
        }

        // The site's colour: what is in force, defaults included; the seven days as seven fields.
        $values[self::field(Theme::MODE)] = Theme::mode();
        $values[self::field(Theme::COLOR)] = Theme::hex(Settings::get(Theme::COLOR)) ?? Theme::GREEN;
        $values[self::field(Theme::MOURNING)] = Theme::isMourning();
        unset($values[self::field(Theme::DAYS)]);

        foreach (Theme::days() as $day => $colour) {
            $values['theme_day_'.$day] = $colour;
        }

        // Kept only when an administrator has arranged something; listed in full either way.
        unset($values['menu__top'], $values['menu__tabs']);
        $values['menu_top'] = array_map(fn (string $key) => ['key' => $key], Menus::keys('top'));
        $values['menu_tabs'] = array_map(fn (string $key) => ['key' => $key], Menus::keys('tabs'));

        // Kept only when an administrator has arranged something; listed in full either way.
        unset($values['home__layout']);
        $values['home_layout'] = HomeLayout::rows();

        // Lists are shown as rows; what News lists is shown as ticks on what is there, kept as what is left out.
        unset($values['menu__links'], $values['menu__news_hidden'], $values['footer__links']);
        $values['menu_links'] = Menus::cleanLinks(Settings::get('menu.links'));
        $values['menu_news_shown'] = array_values(array_diff(array_keys(Menus::NEWS_OPTIONAL), Menus::newsHidden()));
        $values['footer_links'] = Footer::links();
        $values[self::field('footer.show_visits')] = Footer::showsVisits();
        $values[self::field('site.show_logo')] = Settings::showsLogo();

        unset($values['wording']);
        $values['wording_rows'] = Wording::rows();

        // The photographs of the first screen, as rows; the one photograph of before is the first of them.
        unset($values[self::field('site.hero_slides')], $values[self::field('site.hero_image')]);
        $values['hero_slides'] = Settings::heroRows();
        $values[self::field('site.hero_welcome')] = (bool) Settings::get('site.hero_welcome', true);

        $this->form->fill($values);
    }

    public function save(): void
    {
        $values = [];

        foreach ($this->form->getState() as $field => $value) {
            $values[str_replace('__', '.', $field)] = $value;
        }

        $values[Theme::DAYS] = array_map(fn (int $day) => Theme::hex($values['theme_day_'.$day] ?? null) ?? Theme::days()[$day], array_keys(Theme::DAY_COLOURS));

        $values['menu.links'] = Menus::cleanLinks($values['menu_links'] ?? []);
        $values['menu.news_hidden'] = array_values(array_diff(array_keys(Menus::NEWS_OPTIONAL), (array) ($values['menu_news_shown'] ?? [])));
        $values['footer.links'] = Footer::links((array) ($values['footer_links'] ?? []));
        $values['wording'] = Wording::clean((array) ($values['wording_rows'] ?? []));
        $values['site.hero_slides'] = Settings::heroRows((array) ($values['hero_slides'] ?? []));
        $values['site.hero_image'] = $values['site.hero_slides'][0]['image'] ?? null;
        $values['home.layout'] = HomeLayout::toStore((array) ($values['home_layout'] ?? []));
        $values['menu.top'] = Menus::toStore('top', (array) ($values['menu_top'] ?? []));
        $values['menu.tabs'] = Menus::toStore('tabs', (array) ($values['menu_tabs'] ?? []));

        $before = Settings::saved();
        Settings::save($values);
        $after = Settings::saved();

        // Which settings changed, for the access log.
        StaffLog::settings(array_keys(array_filter($after, fn ($value, $key) => ($before[$key] ?? null) != $value, ARRAY_FILTER_USE_BOTH)));

        Notification::make()->success()->title(__('Settings saved'))->body(__('They apply from the next page anyone opens.'))->send();
    }
}
