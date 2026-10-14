{{--
    /p/executives: who runs the hospital. The director, set large; then the heads of the groups of work, a card
    each, with what each is by profession and the group (or groups) each heads. Someone who heads two groups is
    one card with both. A photograph where the hospital gave one; otherwise the first letter of the name.

    Read from the panel like the organisation chart (App\Support\Organisation): the director from Site settings
    > Organisation, the heads from Hospital > Organisation structure.
--}}
@php
    use App\Support\Organisation;

    $director = Organisation::director();
    $mission = Organisation::missionGroup();
    $heads = Organisation::heads();
    $figures = Organisation::figures();
@endphp
<x-layouts.site :title="$page->localTitle()" :description="__('The director of :name and the heads of its groups of work.', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('About us')" :title="$page->localTitle()" icon="user-group" width="max-w-6xl"
                        :lead="__('The director of the hospital and the heads of its groups of work.')">
        <dl class="flex flex-wrap gap-x-10 gap-y-4">
            @foreach (array_filter([[count($heads) + ($director ? 1 : 0), 'Executive staff'], [$figures['groups'], 'Groups of work'], [$figures['people'], 'People working here']], fn ($one) => $one[0]) as [$figure, $label])
                <div>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($figure) }}</dd>
                    <dt class="text-sm text-muted">{{ __($label) }}</dt>
                </div>
            @endforeach
        </dl>
    </x-site.page-header>

    <div class="wrap max-w-6xl py-10 sm:py-14">
        @unless ($page->is_published)
            <p class="mb-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
        @endunless

        @if (! $director && $heads === [])
            <x-site.empty icon="user-group">{{ __('The executives have not been entered yet.') }}</x-site.empty>
        @endif

        @if ($director)
            <section aria-labelledby="director-heading">
                <h2 id="director-heading" class="eyebrow text-center">{{ __('Head of the hospital') }}</h2>
                <div class="exec-top card mt-4">
                    <x-site.person :name="$director['name']" :photo="$director['photo']" class="exec-top-photo" />
                    <div class="min-w-0">
                        <p class="hero-name text-3xl leading-tight sm:text-4xl">{{ $director['name'] }}</p>
                        <p class="mt-2 text-lg text-muted">{{ $director['title'] }}</p>
                        @if ($mission)
                            <p class="mt-4 inline-flex items-center gap-2 rounded-full border border-brand px-3 py-1 text-sm font-medium text-brand">
                                <x-heroicon-m-squares-2x2 class="size-4" aria-hidden="true" />{{ __('Head of :group', ['group' => $mission]) }}
                            </p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($heads)
            <section class="mt-14" aria-labelledby="heads-heading">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">{{ __('Groups of work') }}</p>
                        <h2 id="heads-heading" class="mt-1 text-2xl sm:text-3xl">{{ __('Heads of the groups of work') }}</h2>
                    </div>
                    <a href="{{ route('pages.show', 'structure') }}" class="btn btn-line"><x-heroicon-m-rectangle-group class="size-5" aria-hidden="true" />{{ __('Organisation structure') }}</a>
                </div>

                <ul class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($heads as $head)
                        <li class="exec card">
                            <x-site.person :name="$head['name']" :photo="$head['photo']" class="exec-photo" />
                            {{-- A name breaks between its parts and never inside one. --}}
                            <p class="mt-4 text-lg font-semibold leading-snug">@foreach (preg_split('/\s+/u', $head['name']) as $part)<span class="inline-block">{{ $part }}</span> @endforeach</p>
                            @if ($head['position'])
                                <p class="mt-1 text-sm text-muted">{{ $head['position'] }}</p>
                            @endif
                            <ul class="mt-4 w-full space-y-1.5 border-t border-line pt-4 text-sm">
                                @foreach ($head['groups'] as $group)
                                    <li class="flex items-start justify-center gap-2 text-left">
                                        <x-heroicon-m-check-badge class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
                                        <span>{{ __('Head of :group', ['group' => $group->localName()]) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($director || $heads)
            <x-site.updated :at="Organisation::groups()->max('updated_at') ?? $page->updated_at" class="mt-10 text-center" />
        @endif

        <x-site.page-neighbours :page="$page" class="mt-12" />
    </div>
</x-layouts.site>
