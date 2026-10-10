<?php

namespace App\Filament\Concerns;

use App\Support\Footer;
use App\Support\HomeLayout;
use App\Support\Menus;
use App\Support\SiteSettings as Settings;
use App\Support\Theme;
use App\Support\Wording;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
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
                Section::make(__('Urgent notice'))
                    ->description(__('One line across the top of every page, for what visitors must know today: a clinic that is closed, a change of hours. Remember to switch it off afterwards.'))
                    ->columns(2)
                    ->components([
                        Toggle::make(self::field('alert.enabled'))
                            ->label(__('Show the notice'))
                            ->columnSpanFull(),
                        Textarea::make(self::field('alert.text'))
                            ->label(__('What it says'))
                            ->rows(2)
                            ->maxLength(200)
                            ->requiredIfAccepted(self::field('alert.enabled'))
                            ->placeholder(__('The dental clinic is closed this Friday, 17 October.')),
                        Textarea::make(self::field('alert.text_en'))
                            ->label(__('In English (optional)'))
                            ->rows(2)
                            ->maxLength(200),
                        self::link('alert.url', __('Link (optional)'), 'https://…')
                            ->helperText(__('An announcement with the details, for example.'))
                            ->columnSpanFull(),
                    ]),

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

                Section::make(__('Home page layout'))
                    ->description(__('Put the sections of the home page in the order you want, and switch off the ones you do not want.'))
                    ->collapsed()
                    ->components([
                        Repeater::make('home_layout')
                            ->label(__('Home page layout'))
                            ->hiddenLabel()
                            ->helperText(__('A section with nothing in it (no slide, no article yet) takes no room, shown or not.'))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state): string => __(HomeLayout::SECTIONS[$state['key'] ?? ''] ?? ''))
                            ->components([
                                Hidden::make('key'),
                                Toggle::make('shown')
                                    ->label(__('Shown'))
                                    // The heading of the page and the phone buttons cannot be hidden.
                                    ->disabled(fn (Get $get): bool => in_array($get('key'), HomeLayout::LOCKED, true))
                                    ->dehydrated(),
                            ]),
                    ]),

                Section::make(__('Menus'))
                    ->description(__('Which links are in the top bar of a wide screen, and which tabs are in the dock at the bottom of a phone or tablet, in the order you want.'))
                    ->columns(2)
                    ->collapsed()
                    ->components([
                        self::menu('menu_top', 'top', __('Top bar'), __('Up to :count. On a phone these are not shown; the dock is there instead.', ['count' => Menus::max('top')])),
                        self::menu('menu_tabs', 'tabs', __('Dock on phones and tablets'), __('Up to :count.', ['count' => Menus::max('tabs')])),
                        CheckboxList::make('menu_news_shown')
                            ->label(__('"News" also lists'))
                            ->options(array_map('__', Menus::NEWS_OPTIONAL))
                            ->helperText(__('The types of announcement are always listed; arrange those under Announcements > Types.'))
                            ->columnSpanFull(),
                        Repeater::make('menu_links')
                            ->label(__('Links of your own'))
                            ->helperText(__('To another website or to a page of this one. Up to :count.', ['count' => Menus::MAX_OWN]))
                            ->schema([
                                TextInput::make('label')->label(__('Name'))->required()->maxLength(60),
                                TextInput::make('label_en')->label(__('Name in English (optional)'))->maxLength(60),
                                TextInput::make('url')->label(__('Web address'))->required()->url()->startsWith(['https://', 'http://'])->maxLength(500),
                                Select::make('place')->label(__('Where it shows'))->options(array_map('__', Menus::OWN_PLACES))->default('top')->required()->selectablePlaceholder(false),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(Menus::MAX_OWN)
                            ->addActionLabel(__('Add a link'))
                            ->reorderableWithButtons()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Footer'))
                    ->description(__('The foot of every page. The address, phone numbers and social links there come from "Contact" and "Social media" below; the pages listed there are chosen under System > Pages.'))
                    ->columns(2)
                    ->collapsed()
                    ->components([
                        Textarea::make(self::field('footer.about'))
                            ->label(__('A few words about the hospital'))
                            ->rows(3)
                            ->maxLength(500),
                        Textarea::make(self::field('footer.about_en'))
                            ->label(__('In English (optional)'))
                            ->rows(3)
                            ->maxLength(500),
                        Repeater::make('footer_links')
                            ->label(__('Links of your own'))
                            ->helperText(__('Links with the same heading are shown together as a column. Up to :count.', ['count' => Footer::MAX_LINKS]))
                            ->schema([
                                TextInput::make('group')->label(__('Heading'))->maxLength(60)->placeholder(__('Related agencies'))
                                    // The headings already in use are offered, so that one is not spelt two ways.
                                    ->datalist(fn () => array_values(array_unique(array_filter(array_column(Footer::links(), 'group'))))),
                                TextInput::make('label')->label(__('Name'))->required()->maxLength(100),
                                TextInput::make('url')->label(__('Web address'))->required()->url()->startsWith(['https://', 'http://'])->maxLength(500),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->maxItems(Footer::MAX_LINKS)
                            ->addActionLabel(__('Add a link'))
                            ->reorderableWithButtons()
                            ->columnSpanFull(),
                        TextInput::make(self::field('footer.copyright'))
                            ->label(__('Line at the very bottom (optional)'))
                            ->maxLength(200)
                            ->helperText(__('Left blank: "©", the year and the name of the hospital.')),
                        Toggle::make(self::field('footer.show_visits'))
                            ->label(__('Show how many pages have been opened'))
                            ->inline(false),
                    ]),

                Section::make(__('Wording'))
                    ->description(__('Change what the site says, without touching its files. Search for a phrase as it reads on the site, in Thai or English, and write what it should say instead. Leave a language empty to keep its usual wording.'))
                    ->collapsed()
                    ->components([
                        Repeater::make('wording_rows')
                            ->label(__('Wording'))
                            ->hiddenLabel()
                            ->helperText(__('Keep words that begin with a colon, such as :name or :count, as they are: the site fills them in.'))
                            ->addActionLabel(__('Rewrite a phrase'))
                            ->defaultItems(0)
                            ->maxItems(Wording::MAX)
                            ->reorderable(false)
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => filled($state['key'] ?? null) ? (string) str($state['key'])->limit(70) : null)
                            ->components([
                                Select::make('key')
                                    ->label(__('The phrase'))
                                    ->searchable()
                                    ->required()
                                    ->getSearchResultsUsing(fn (string $search): array => Wording::search($search))
                                    ->getOptionLabelUsing(fn (?string $value): ?string => $value === null ? null : Wording::label($value))
                                    ->columnSpanFull(),
                                Textarea::make('th')->label(__('In Thai'))->rows(2)->maxLength(500),
                                Textarea::make('en')->label(__('In English'))->rows(2)->maxLength(500),
                            ]),
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
                        TextInput::make(self::field('site.motto'))
                            ->label(__('Motto (optional)'))
                            ->maxLength(200)
                            ->helperText(__('Shown under the name on the home page.'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Identity of the organisation'))
                    ->description(__('Shown on the home page, one tab each. One item on each line. Leave all three blank to hide the section.'))
                    ->components([
                        Textarea::make(self::field('identity.vision'))
                            ->label(__('Vision'))
                            ->rows(4)
                            ->maxLength(2000)
                            ->helperText(__('The first line is shown large; the lines after it explain it.')),
                        Textarea::make(self::field('identity.mission'))
                            ->label(__('Mission'))
                            ->rows(5)
                            ->maxLength(2000),
                        Textarea::make(self::field('identity.values'))
                            ->label(__('Core values'))
                            ->rows(7)
                            ->maxLength(2000),
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
                        Textarea::make(self::field('contact.hours'))
                            ->label(__('Opening hours'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        TextInput::make(self::field('contact.map'))
                            ->label(__('Map'))
                            ->maxLength(1000)
                            ->regex(Settings::MAP_PATTERN)
                            ->validationMessages(['regex' => __('Paste the address that starts with https://www.google.com/maps/embed')])
                            ->helperText(__('In Google Maps: Share > Embed a map, then copy only the address inside src="…".'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Complaints and suggestions'))
                    ->description(__('Links on the contact page to forms the hospital runs elsewhere, such as Google Forms. Leave one blank to hide it.'))
                    ->columns(2)
                    ->collapsed()
                    ->components([
                        self::link('feedback.complaint', __('Make a complaint'), 'https://docs.google.com/forms/…')->startsWith('https://'),
                        self::link('feedback.suggestion', __('Make a suggestion'), 'https://forms.gle/…')->startsWith('https://'),
                        self::link('feedback.survey', __('Satisfaction survey'), 'https://docs.google.com/forms/…')->startsWith('https://'),
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

    /** One of the two menus: an ordered list of places, each picked once. */
    private static function menu(string $field, string $menu, string $label, string $help): Repeater
    {
        return Repeater::make($field)
            ->label($label)
            ->helperText($help)
            ->simple(
                Select::make('key')
                    ->label(__('Place'))
                    ->options(collect(Menus::PLACES)->map(fn (array $place) => __($place[1]))->all())
                    ->required()
                    ->distinct()
                    ->selectablePlaceholder(false),
            )
            ->addActionLabel(__('Add a place'))
            ->minItems(1)
            ->maxItems(Menus::max($menu))
            ->reorderableWithButtons();
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
