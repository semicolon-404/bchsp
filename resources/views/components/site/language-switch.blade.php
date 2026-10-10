@use('App\Http\Middleware\SetLocale')

{{--
    ไทย / English. Each is a plain link: changing language reloads the page so every part of it
    comes back in the new language. Each language is written in itself, so it can be found by
    someone who cannot read the other.
--}}
<div role="group" aria-label="{{ __('Language') }}" {{ $attributes->class('switch grid-cols-2') }}>
    @foreach (SetLocale::LANGUAGES as $code => $name)
        <a href="{{ route('language', $code) }}" lang="{{ $code }}" rel="nofollow" aria-current="{{ app()->getLocale() === $code ? 'true' : 'false' }}">{{ $name }}</a>
    @endforeach
</div>
