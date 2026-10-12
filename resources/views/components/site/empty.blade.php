{{--
    A part of the site with nothing in it yet: a quiet mark and a line saying so, in place of a bare sentence.

    <x-site.empty class="mt-8" />                                  "Nothing has been added here yet."
    <x-site.empty icon="megaphone">{{ __('...') }}</x-site.empty>  with words and a mark of its own
--}}
@props(['icon' => 'inbox'])

<div {{ $attributes->class('card flex flex-col items-center gap-3 border-dashed py-10 text-center text-muted') }}>
    <span class="flex size-14 items-center justify-center rounded-full bg-raised text-brand" aria-hidden="true">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-7" />
    </span>
    <p>{{ $slot->isEmpty() ? __('Nothing has been added here yet.') : $slot }}</p>
</div>
