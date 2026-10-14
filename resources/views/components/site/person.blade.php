{{--
    A person's round picture. Without a photograph, the first letter of the name itself (not of the title
    before it) on the colour of the site. Decoration: the name is always written out beside it.

    <x-site.person :name="$head['name']" :photo="$head['photo']" :focus="$head['focus']" class="size-16 text-2xl" />

    focus: the part of the photograph that shows, as chosen by dragging in the panel (Person::photoPosition).
    Left out, it is near the top, where a face usually is; the middle of a portrait is the clothes.
--}}
@props(['name', 'photo' => null, 'focus' => null])

@if ($photo)
    <img src="{{ $photo }}" alt="" loading="lazy" {{ $attributes->class('person shrink-0 rounded-full object-cover') }} style="object-position: {{ $focus ?: App\Models\Person::FOCUS }}">
@else
    <span {{ $attributes->class('person flex shrink-0 items-center justify-center rounded-full font-semibold') }} aria-hidden="true">{{ App\Support\Organisation::initial($name) }}</span>
@endif
