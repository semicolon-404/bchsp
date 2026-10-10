<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\SiteSettingsForm;
use App\Filament\Concerns\TranslatesTitle;
use App\Support\SiteSettings as Settings;
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

        $this->form->fill($values);
    }

    public function save(): void
    {
        $values = [];

        foreach ($this->form->getState() as $field => $value) {
            $values[str_replace('__', '.', $field)] = $value;
        }

        Settings::save($values);

        Notification::make()->success()->title(__('Settings saved'))->body(__('They apply from the next page anyone opens.'))->send();
    }
}
