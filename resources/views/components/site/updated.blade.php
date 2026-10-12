{{--
    When a page was last changed, as a reader in Thailand writes the day: "แก้ไขล่าสุด 12 ต.ค. 2569".
    Said on pages whose worth depends on being kept up: About us, ITA, a department's profile.

    <x-site.updated :at="$page->updated_at" class="mt-10" />
--}}
@props(['at'])

@if ($at)
    <p {{ $attributes->class('font-mono text-sm text-muted') }}>
        {{ __('Last changed') }}
        <time datetime="{{ $at->toDateString() }}">{{ app()->getLocale() === 'th' ? $at->locale('th')->translatedFormat('j M ').($at->year + 543) : $at->locale('en')->translatedFormat('j M Y') }}</time>
    </p>
@endif
