<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\SiteSettingsForm;
use App\Filament\Concerns\TranslatesTitle;
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
        $values[self::field(Theme::MODE)] = Theme::isDaily() ? 'daily' : 'fixed';
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

        unset($values['wording']);
        $values['wording_rows'] = Wording::rows();

        $this->form->fill($values);
    }

    public function save(): void
    {
        $values = [];

        foreach ($this->form->getState() as $field => $value) {
            $values[str_replace('__', '.', $field)] = $value;
        }

        $values[Theme::DAYS] = array_map(fn (int $day) => Theme::hex($values['theme_day_'.$day] ?? null) ?? Theme::days()[$day], array_keys(Theme::DAY_COLOURS));

        $values['wording'] = Wording::clean((array) ($values['wording_rows'] ?? []));
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
