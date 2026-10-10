{{--
    The urgent notice across the top of every page, when an administrator has switched one on
    (System > Site settings > "Urgent notice"): "The dental clinic is closed this Friday".
    One line, said once to screen readers as the page opens. Faster to put up than a poster.
--}}
@php
    use App\Support\SiteSettings;

    $english = app()->getLocale() === 'en' ? SiteSettings::get('alert.text_en') : null;
    $text = SiteSettings::get('alert.enabled') ? ($english ?: SiteSettings::get('alert.text')) : null;
    $url = SiteSettings::get('alert.url');
    $url = is_string($url) && preg_match('~^https?://~i', $url) ? $url : null;
@endphp

@if (filled($text))
    <div class="bg-danger text-on-danger" role="status">
        <p class="wrap flex items-start justify-center gap-2 py-2 text-center text-sm font-medium">
            <x-heroicon-s-exclamation-triangle class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <span>
                {{ $text }}
                @if ($url)
                    <a href="{{ $url }}" class="ml-1 whitespace-nowrap font-semibold underline">{{ __('Read more') }}</a>
                @endif
            </span>
        </p>
    </div>
@endif
