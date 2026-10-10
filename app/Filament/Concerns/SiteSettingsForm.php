<?php

namespace App\Filament\Concerns;

use App\Support\Theme;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                Section::make(__('Maintenance'))
                    ->description(__('Close the website while something is being worked on. Administrators carry on as usual; visitors see a short "Back soon" page with the phone numbers.'))
                    ->columns(2)
                    ->components([
                        Toggle::make(self::field('maintenance.enabled'))
                            ->label(__('Closed for maintenance'))
                            ->helperText(__('Visitors are stopped at once. Remember to switch it off again.')),
                        Textarea::make(self::field('maintenance.message'))
                            ->label(__('What the page says (optional)'))
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder(__('We are working on the website right now. Please come back in a little while.')),
                    ]),

                Section::make(__('Colour of the site'))
                    ->description(__('The one colour that buttons, links and highlights wear, on the website and in this panel. Pick one, or let it follow the day of the week. It applies from the next page anyone opens.'))
                    ->columns(2)
                    ->components([
                        Radio::make(self::field(Theme::MODE))
                            ->label(__('The site wears'))
                            ->options(['fixed' => __('One colour'), 'daily' => __('The colour of the day')])
                            ->live()
                            ->columnSpanFull(),
                        Select::make('theme_pick')
                            ->label(__('Ready-made colours'))
                            ->placeholder(__('Choose one, or set your own beside this'))
                            ->options([
                                __('Favourites') => array_map(fn (string $name) => __($name), Theme::FAVOURITES),
                                __('Colours of the days') => array_combine(Theme::DAY_COLOURS, array_map(fn (string $day) => __($day), Theme::DAY_NAMES)),
                            ])
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $state ? $set(self::field(Theme::COLOR), $state) : null)
                            ->visible(fn (Get $get) => $get(self::field(Theme::MODE)) !== 'daily'),
                        ColorPicker::make(self::field(Theme::COLOR))
                            ->label(__('The colour'))
                            ->regex('/^#[0-9a-fA-F]{6}$/')
                            ->required(fn (Get $get) => $get(self::field(Theme::MODE)) !== 'daily')
                            ->helperText(__('A colour too dark or too pale to read is adjusted by itself, and words on it turn black or white to suit.'))
                            ->visible(fn (Get $get) => $get(self::field(Theme::MODE)) !== 'daily'),
                        ...array_map(
                            fn (int $day) => ColorPicker::make('theme_day_'.$day)
                                ->label(__(Theme::DAY_NAMES[$day]))
                                ->regex('/^#[0-9a-fA-F]{6}$/')
                                ->visible(fn (Get $get) => $get(self::field(Theme::MODE)) === 'daily'),
                            array_keys(Theme::DAY_NAMES),
                        ),
                        Toggle::make(self::field(Theme::MOURNING))
                            ->label(__('Mourning: the whole site in black and white'))
                            ->helperText(__('Every page and picture turns grey, here in the panel too, until this is switched off again.'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('The hospital'))
                    ->description(__('The name and logo shown at the top of every page and in this panel.'))
                    ->columns(2)
                    ->components([
                        TextInput::make(self::field('app.name'))
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(120),
                        TextInput::make(self::field('site.name_en'))
                            ->label(__('Name in English (optional)'))
                            ->maxLength(120)
                            ->helperText(__('Shown to visitors who switch the website to English. Left blank, they see the name above.')),
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
