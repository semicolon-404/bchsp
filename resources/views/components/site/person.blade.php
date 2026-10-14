{{--
    A person's round picture. Without a photograph, the first letter of the name itself (not of the title
    before it) on the colour of the site. Decoration: the name is always written out beside it.

    <x-site.person :name="$head['name']" :photo="$head['photo']" class="size-16 text-2xl" />
--}}
@props(['name', 'photo' => null])

@if ($photo)
    <img src="{{ $photo }}" alt="" loading="lazy" {{ $attributes->class('person shrink-0 rounded-full object-cover') }}>
@else
    <span {{ $attributes->class('person flex shrink-0 items-center justify-center rounded-full font-semibold') }} aria-hidden="true">{{ App\Support\Organisation::initial($name) }}</span>
@endif
