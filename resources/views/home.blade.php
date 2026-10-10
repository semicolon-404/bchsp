{{-- The home page. Its sections (announcements, articles, departments...) are added as each is built. --}}
@php
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $email = SiteSettings::get('contact.email');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);

    // What the "editor window" lists: only what has been filled in.
    $facts = array_filter(['phone' => $phone, 'emergency' => $emergency, 'email' => $email]);
@endphp
<x-layouts.site :description="__('News, announcements and services of :name.', ['name' => $name])">
    <section class="border-b border-line">
        <div class="wrap grid items-center gap-10 py-14 sm:py-20 lg:grid-cols-2">
            <div class="text-center lg:text-left">
                <p class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1 text-sm text-muted">
                    <span class="size-2 animate-pulse-dot rounded-full bg-brand" aria-hidden="true"></span>
                    {{ __('Caring for the community') }}
                </p>
                <h1 class="mt-6 text-3xl sm:text-5xl">{{ $name }}</h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-muted lg:mx-0">{{ __('News, announcements and services of the hospital, in one place.') }}</p>

                @if ($phone || $emergency)
                    <div class="mt-8 flex flex-wrap justify-center gap-3 lg:justify-start">
                        @if ($phone)
                            <a href="{{ $dial($phone) }}" class="btn btn-brand">
                                <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                                {{ __('Call :number', ['number' => $phone]) }}
                            </a>
                        @endif
                        @if ($emergency)
                            <a href="{{ $dial($emergency) }}" class="btn btn-line">
                                {{ __('Emergency :number', ['number' => $emergency]) }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- The same contact details again, drawn as a file open in an editor. Decoration: screen readers skip it. --}}
            @if ($facts)
                <div class="code-block" aria-hidden="true">
                    <div class="code-header">
                        <span class="code-dot bg-danger"></span>
                        <span class="code-dot bg-syn-string"></span>
                        <span class="code-dot bg-syn-function"></span>
                        <span class="ml-3 font-mono text-sm text-muted">contact.ts</span>
                    </div>
                    <pre class="code-body"><code><span class="text-syn-comment">// {{ $name }}</span>
<span class="text-syn-keyword">const</span> <span class="text-syn-variable">hospital</span> = {
@foreach ($facts as $key => $value)
  <span class="text-syn-function">{{ $key }}</span>: <span class="text-syn-string">"{{ $value }}"</span>,
@endforeach
};</code></pre>
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
