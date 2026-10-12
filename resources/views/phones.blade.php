{{--
    /phones: the hospital's numbers on one page: the main ones from Site settings, then every department that
    has given a number (its own page keeps it). The box above the list narrows it as one types
    (resources/js/filter.js); without scripts the whole list is simply there.
--}}
@php
    use App\Support\SiteSettings;

    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $departments = App\Models\Department::shown()->whereNotNull('phone')->where('phone', '!=', '')->get();
@endphp
<x-layouts.site :title="__('Phone numbers')" :description="__('The phone numbers of :name and of each of its departments.', ['name' => SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Contact us') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Phone numbers') }}</h1>

        @if ($phone || $emergency)
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                @if ($emergency)
                    <a href="{{ $dial($emergency) }}" class="card border-danger text-ink no-underline">
                        <span class="block text-sm text-danger">{{ __('Emergency, 24 hours a day') }}</span>
                        <span class="block font-mono text-3xl font-semibold text-danger">{{ $emergency }}</span>
                    </a>
                @endif
                @if ($phone)
                    <a href="{{ $dial($phone) }}" class="card text-ink no-underline">
                        <span class="block text-sm text-muted">{{ __('The hospital') }}</span>
                        <span class="block font-mono text-3xl font-semibold text-brand">{{ $phone }}</span>
                    </a>
                @endif
            </div>
        @endif

        @if ($departments->isEmpty())
            <x-site.empty class="mt-8" icon="phone" />
        @else
            <label class="mt-8 block">
                <span class="mb-1 block text-sm text-muted">{{ __('Find a department') }}</span>
                <input type="search" data-filter="#phone-list" placeholder="{{ __('e.g. dental, pharmacy') }}" class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-ink placeholder:text-muted" hidden>
            </label>

            <ul id="phone-list" class="card mt-3 p-0">
                @foreach ($departments as $department)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-line px-4 py-3 last:border-b-0" data-filter-text="{{ mb_strtolower($department->name.' '.$department->name_en.' '.$department->location) }}">
                        <x-site.department-avatar :department="$department" class="size-10 text-base" />
                        <span class="min-w-0 flex-1 basis-40">
                            <a href="{{ route('departments.show', $department) }}" class="font-medium text-ink no-underline hover:text-brand">{{ $department->localName() }}</a>
                            @if ($department->location)
                                <span class="block text-sm text-muted">{{ $department->location }}</span>
                            @endif
                        </span>
                        @if ($department->dialUrl())
                            <a href="{{ $department->dialUrl() }}" class="font-mono font-semibold text-brand underline">{{ $department->phone }}</a>
                        @else
                            <span class="font-mono">{{ $department->phone }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 hidden text-muted" data-filter-none>{{ __('No department matches.') }}</p>
        @endif
    </div>
</x-layouts.site>
