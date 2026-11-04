{{--
    The cover of an announcement that was given no picture: drawn from what is known of it, so a page of
    announcements without pictures does not look like a page of empty boxes. The colour and the symbol are those
    of its kind (App\Models\AnnouncementType), over a fine pattern; in the middle the hospital's logo when it has
    one (Site settings), its kind's symbol otherwise; in a corner, the day it came out, large.
    Decoration only: the title, kind and date are always written out beside it.

    The size and shape come from the caller:
        <x-site.cover :announcement="$one" class="aspect-[4/3] w-full" />
        <x-site.cover :announcement="$one" class="absolute inset-0" large />      the symbol and the day larger
        <x-site.cover :announcement="$one" class="w-28 shrink-0" plain />        too small for the day
--}}
@props(['announcement', 'large' => false, 'plain' => false])
@php
    $type = $announcement->type;
    $day = $announcement->starts_at ?? $announcement->created_at;
    $logo = App\Support\SiteSettings::logoUrl();
@endphp
<span {{ $attributes->class(['cover', 'cover-large' => $large]) }} style="--tone: {{ $type?->colour() ?? 'var(--color-brand)' }}" aria-hidden="true">
    @if ($logo)
        <span @class(['cover-logo', 'size-36' => $large, 'size-24' => ! $large && ! $plain, 'size-14' => $plain])>
            <img src="{{ $logo }}" alt="" loading="lazy">
            <x-dynamic-component :component="'heroicon-s-'.($type?->icon() ?? 'document-text')" class="cover-logo-kind" />
        </span>
    @else
        <x-dynamic-component :component="'heroicon-o-'.($type?->icon() ?? 'document-text')" @class(['cover-mark', 'size-28' => $large, 'size-16' => ! $large && ! $plain, 'size-10' => $plain]) />
    @endif
    @unless ($plain)
        <span @class(['cover-day', 'text-5xl' => $large, 'text-3xl' => ! $large])>
            {{ $day->format('j') }}
            <small>{{ $day->locale(app()->getLocale())->translatedFormat('M') }} {{ $day->year + (app()->getLocale() === 'th' ? 543 : 0) }}</small>
        </span>
    @endunless
</span>
