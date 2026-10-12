{{--
    A department's small round picture. Without one, the first letter of its name on the colour of the site.
    The size comes from the caller: <x-site.department-avatar :department="$department" class="size-10 text-base" />
--}}
@props(['department'])

@if ($avatar = $department->avatarUrl())
    <img src="{{ $avatar }}" alt="" {{ $attributes->class('shrink-0 rounded-full border border-line bg-surface object-cover') }}>
@else
    <span {{ $attributes->class('flex shrink-0 items-center justify-center rounded-full bg-brand font-semibold text-on-brand') }} aria-hidden="true">{{ $department->initial() }}</span>
@endif
