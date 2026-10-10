<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The form of the Site settings page (App\Filament\Pages\SiteSettings): its sections, in the
 * order they show. The page itself keeps what is filled in when it opens and what happens on Save.
 *
 * Field names use "__" where the setting has a ".": see the page.
 */
trait SiteSettingsForm
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('The hospital'))
                    ->description(__('The name and logo shown at the top of every page and in this panel.'))
                    ->columns(2)
                    ->components([
                        TextInput::make(self::field('app.name'))
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(120),
                        FileUpload::make(self::field('site.logo'))
                            ->label(__('Logo (optional)'))
                            ->image()
                            // No SVG: it can carry a script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('site')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText(__('PNG, JPG or WebP, up to 2 MB. A square or wide picture on a clear background reads best.')),
                    ]),

                Section::make(__('Contact'))
                    ->description(__('Shown in the footer of every page and on the contact page. Leave one blank to hide it.'))
                    ->columns(2)
                    ->components([
                        Textarea::make(self::field('contact.address'))
                            ->label(__('Address'))
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TextInput::make(self::field('contact.phone'))
                            ->label(__('Phone'))
                            ->tel()
                            ->maxLength(50),
                        TextInput::make(self::field('contact.emergency_phone'))
                            ->label(__('Emergency phone'))
                            ->tel()
                            ->maxLength(50)
                            ->helperText(__('Shown larger than the rest, where someone in a hurry looks first.')),
                        TextInput::make(self::field('contact.email'))
                            ->label(__('Email'))
                            ->email()
                            ->maxLength(255),
                    ]),

                Section::make(__('Social media'))
                    ->description(__('Links in the footer. Leave one blank to hide it.'))
                    ->columns(2)
                    ->components([
                        self::link('social.facebook', 'Facebook', 'https://www.facebook.com/…'),
                        self::link('social.line', 'LINE', 'https://line.me/…'),
                        self::link('social.youtube', 'YouTube', 'https://www.youtube.com/@…'),
                    ]),

                Section::make(__('Security'))
                    ->components([
                        Toggle::make(self::field('bchsp.security.staff_two_step'))
                            ->label(__('Everyone who uses this panel must have two-step sign-in'))
                            ->helperText(__('Someone who has not set it up is taken to do so before anything else. Set up your own first: it applies to you too.')),
                    ]),
            ]);
    }

    /** A web address, with an example of one. */
    private static function link(string $key, string $label, string $example): TextInput
    {
        return TextInput::make(self::field($key))->label($label)->url()->maxLength(255)->placeholder($example);
    }

    private static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }
}
