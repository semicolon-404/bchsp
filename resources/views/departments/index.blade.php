{{-- /departments: every unit that is shown, in the administrator's order. --}}
<x-layouts.site :title="__('Departments')" :description="__('The departments of :name: where each is, its phone number and its hours.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Departments') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Departments and services') }}</h1>

        @if ($departments->isEmpty())
            <x-site.empty class="mt-8" icon="building-office-2" />
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($departments as $department)
                    <x-site.department-card :department="$department" />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.site>
