{{-- /search: one box, and what was found, kind by kind (App\Http\Controllers\SearchController). --}}
<x-layouts.site :title="__('Search')" :noindex="true">
    <div class="wrap max-w-3xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Search') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Search this website') }}</h1>

        {{-- Sent with GET: the address can be shared, and nothing typed here is kept by the site. --}}
        <form action="{{ route('search') }}" method="get" role="search" class="mt-6 flex gap-2">
            <label class="sr-only" for="q">{{ __('Words to look for') }}</label>
            <input id="q" type="search" name="q" value="{{ $term }}" maxlength="80" required minlength="{{ App\Http\Controllers\SearchController::MIN }}" autofocus
                   placeholder="{{ __('e.g. vaccine, procurement, dental') }}"
                   class="min-h-11 min-w-0 flex-1 rounded-md border border-line bg-surface px-4 text-ink placeholder:text-muted">
            <button class="btn btn-brand"><x-heroicon-m-magnifying-glass class="size-5" aria-hidden="true" />{{ __('Search') }}</button>
        </form>

        @if ($term !== '')
            <p class="mt-6 text-muted" role="status">
                @if (mb_strlen($term) < App\Http\Controllers\SearchController::MIN)
                    {{ __('Type at least :count characters.', ['count' => App\Http\Controllers\SearchController::MIN]) }}
                @else
                    {{ trans_choice('{0} Nothing was found for ":term".|{1} :count result for ":term"|[2,*] :count results for ":term"', $count, ['count' => $count, 'term' => $term]) }}
                @endif
            </p>
        @endif

        @foreach ($results as $kind => $found)
            <section class="mt-8">
                <h2 class="eyebrow">{{ __($kind) }}</h2>
                <ul class="card mt-3 p-0">
                    @foreach ($found as $result)
                        <li class="border-b border-line last:border-b-0">
                            <a href="{{ $result['url'] }}" class="group flex flex-col gap-1 px-4 py-3 no-underline sm:flex-row sm:items-baseline sm:gap-4">
                                <span class="min-w-0 flex-1 text-ink group-hover:text-brand">{{ $result['title'] }}</span>
                                @if ($result['note'])
                                    <span class="shrink-0 font-mono text-sm text-muted">{{ $result['note'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</x-layouts.site>
