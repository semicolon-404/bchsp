{{--
    The mark of a kind of announcement: its symbol in its colour (App\Models\AnnouncementType). Decoration beside
    the type's name, which is always written out as well: nobody has to tell kinds apart by colour alone.

    <x-site.type-mark :type="$type" />            the symbol in a tinted square, for a row of the list
    <x-site.type-mark :type="$type" bare />       the symbol alone, for a chip or a line of text
--}}
@props(['type' => null, 'bare' => false])

@if ($type)
    @if ($bare)
        <x-dynamic-component :component="'heroicon-m-'.$type->icon()" {{ $attributes->class('size-4 shrink-0') }} style="color: {{ $type->colour() }}" aria-hidden="true" />
    @else
        <span {{ $attributes->class('flex size-9 shrink-0 items-center justify-center rounded-lg') }} style="color: {{ $type->colour() }}; background: color-mix(in srgb, {{ $type->colour() }} 14%, transparent)" aria-hidden="true">
            <x-dynamic-component :component="'heroicon-o-'.$type->icon()" class="size-5" />
        </span>
    @endif
@else
    {{-- An announcement whose type was removed keeps its place in the row. --}}
    @unless ($bare)
        <span {{ $attributes->class('flex size-9 shrink-0 items-center justify-center rounded-lg bg-raised text-muted') }} aria-hidden="true">
            <x-heroicon-o-document-text class="size-5" />
        </span>
    @endunless
@endif
