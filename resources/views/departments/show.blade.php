{{-- /departments/{slug}: one unit of the hospital. --}}
@php($facts = array_filter(['Location' => $department->location, 'Phone' => $department->phone, 'Opening hours' => $department->hours]))
<x-layouts.site :title="$department->localName()" :description="Str::limit($department->localDescription(), 160)">
    <article class="wrap max-w-3xl py-10 sm:py-14">
        <p class="text-sm"><a href="{{ route('departments.index') }}" class="font-semibold text-brand underline">&larr; {{ __('All departments') }}</a></p>

        @unless ($department->is_active)
            {{-- Only an administrator gets this far (DepartmentController::show). --}}
            <p class="mt-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
        @endunless

        <p class="eyebrow mt-6">{{ __('Departments') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $department->localName() }}</h1>

        @if ($image = $department->imageUrl())
            <img src="{{ $image }}" alt="" class="mt-8 w-full rounded-xl border border-line">
        @endif

        @if ($facts)
            <dl class="card mt-8 grid gap-4 sm:grid-cols-[10rem_1fr]">
                @foreach ($facts as $label => $value)
                    <dt class="font-mono text-sm text-syn-function">{{ __($label) }}</dt>
                    <dd class="whitespace-pre-line">
                        @if ($label === 'Phone' && $department->dialUrl())
                            <a href="{{ $department->dialUrl() }}" class="font-semibold text-brand underline">{{ $value }}</a>
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                @endforeach
            </dl>
        @endif

        @if (filled($department->localDescription()))
            <p class="mt-8 whitespace-pre-line">{{ $department->localDescription() }}</p>
        @endif
    </article>
</x-layouts.site>
