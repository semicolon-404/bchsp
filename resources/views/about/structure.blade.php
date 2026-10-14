{{--
    /p/structure: how the hospital is organised, drawn as a chart and not written as text. From the top: the
    director, the mission group the groups of work stand under, then a card for each group of work with who
    heads it, how many work in it (and what share of the hospital that is), and the units of work inside it.
    A box at the top finds a group or a unit by a word of its name.

    Everything is read from the panel: the director from Site settings > Organisation, the groups from
    Hospital > Organisation structure (App\Support\Organisation). A page of the "About us" group like the
    others, so it leads on to the page before and after it.
--}}
@php
    use App\Support\Organisation;

    $director = Organisation::director();
    $mission = Organisation::missionGroup();
    $groups = Organisation::groups();
    $figures = Organisation::figures();
    $people = (int) $groups->sum('staff_count');
    $largest = max(1, (int) $groups->max('staff_count'));
    $units = $groups->sum(fn ($group) => count($group->unitList()));
@endphp
<x-layouts.site :title="$page->localTitle()" :description="__('How :name is organised: its groups of work, who heads each, and the units inside them.', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('About us')" :title="$page->localTitle()" icon="rectangle-group" width="max-w-6xl"
                        :lead="__('The groups of work of the hospital, who heads each, and the units of work inside them.')">
        <div class="flex flex-wrap items-end gap-x-10 gap-y-5">
            <dl class="flex flex-wrap gap-x-10 gap-y-4">
                @foreach (array_filter([[$groups->count(), 'Groups of work'], [$units, 'Units of work'], [$figures['people'], 'People working here']], fn ($one) => $one[0]) as [$figure, $label])
                    <div>
                        <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($figure) }}</dd>
                        <dt class="text-sm text-muted">{{ __($label) }}</dt>
                    </div>
                @endforeach
            </dl>
            @if ($groups->count() > 4)
                <label class="relative block w-full max-w-sm sm:ml-auto">
                    <span class="sr-only">{{ __('Find a group or a unit of work') }}</span>
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" data-filter="#groups" placeholder="{{ __('Find a group or a unit of work') }}" class="min-h-12 w-full rounded-lg border border-line bg-surface py-2 pl-10 pr-3 text-ink placeholder:text-muted">
                </label>
            @endif
        </div>
    </x-site.page-header>

    <div class="wrap max-w-6xl py-10 sm:py-14">
        @unless ($page->is_published)
            <p class="mb-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
        @endunless

        @if ($groups->isEmpty() && ! $director)
            <x-site.empty icon="rectangle-group">{{ __('The organisation chart has not been entered yet.') }}</x-site.empty>
        @else
            <div class="org">
                {{-- The top of the chart: whoever runs the hospital. --}}
                @if ($director)
                    <a href="{{ route('pages.show', 'executives') }}" class="org-top card text-ink no-underline">
                        <x-site.person :name="$director['name']" :photo="$director['photo']" class="size-20 text-3xl" />
                        <span class="min-w-0">
                            <span class="eyebrow block">{{ __('Head of the hospital') }}</span>
                            <span class="mt-1 block text-xl font-semibold sm:text-2xl">{{ $director['name'] }}</span>
                            <span class="block text-muted">{{ $director['title'] }}</span>
                        </span>
                    </a>
                    <span class="org-stem" aria-hidden="true"></span>
                @endif

                {{-- The mission group every group of work stands under. --}}
                @if ($mission)
                    <p class="org-mission">
                        <x-heroicon-o-squares-2x2 class="size-5 shrink-0" aria-hidden="true" />
                        <span class="font-semibold">{{ $mission }}</span>
                        <span class="font-mono text-sm opacity-80">{{ trans_choice('{1} :count group of work|[2,*] :count groups of work', $groups->count(), ['count' => $groups->count()]) }}@if ($people) · {{ trans_choice('{1} :count person|[2,*] :count people', $people, ['count' => number_format($people)]) }}@endif</span>
                    </p>
                    <span class="org-stem" aria-hidden="true"></span>
                @endif

                @if ($groups->isNotEmpty())
                    <ul id="groups" class="org-groups">
                        @foreach ($groups as $group)
                            @php($list = $group->unitList())
                            <li class="org-group card" data-filter-text="{{ $group->localName().' '.$group->headName().' '.implode(' ', $list) }}">
                                <p class="flex items-start gap-3">
                                    <span class="door-mark font-mono text-sm font-semibold" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-lg font-semibold leading-snug">{{ $group->localName() }}</span>
                                        @if ($group->staff_count)
                                            <span class="mt-1 block font-mono text-sm text-muted">{{ trans_choice('{1} :count person|[2,*] :count people', $group->staff_count, ['count' => number_format($group->staff_count)]) }}</span>
                                        @endif
                                    </span>
                                </p>

                                @if ($group->staff_count)
                                    {{-- How large it is beside the largest: read at a glance, and said in figures just above. --}}
                                    <span class="org-share" aria-hidden="true"><span style="width: {{ max(4, round($group->staff_count / $largest * 100)) }}%"></span></span>
                                @endif

                                @if ($group->headName())
                                    <p class="mt-4 flex items-center gap-3 rounded-xl border border-line bg-page px-3 py-2">
                                        <x-site.person :name="$group->headName()" :photo="$group->headPhotoUrl()" class="size-11 text-lg" />
                                        <span class="min-w-0">
                                            <span class="block text-xs text-muted">{{ __('Head of the group') }}</span>
                                            <span class="block font-medium">{{ $group->headName() }}</span>
                                        </span>
                                    </p>
                                @endif

                                @if ($list)
                                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-muted">{{ trans_choice('{1} :count unit of work|[2,*] :count units of work', count($list), ['count' => count($list)]) }}</p>
                                    <ul class="mt-2 space-y-1.5">
                                        @foreach ($list as $unit)
                                            <li class="org-unit">{{ $unit }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($group->department?->is_active)
                                    <p class="mt-auto pt-4"><a href="{{ route('departments.show', $group->department) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand no-underline">{{ __('See the profile') }}<x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a></p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-6 hidden text-center text-muted" data-filter-none>{{ __('Nothing matches. Try another word.') }}</p>
                @endif
            </div>

            <p class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('pages.show', 'executives') }}" class="btn btn-brand"><x-heroicon-m-user-group class="size-5" aria-hidden="true" />{{ __('Executives') }}</a>
                <a href="{{ route('phones') }}" class="btn btn-line"><x-heroicon-m-phone class="size-5" aria-hidden="true" />{{ __('Phone numbers of every department') }}</a>
                <button type="button" class="btn btn-line" data-print hidden><x-heroicon-m-printer class="size-5" aria-hidden="true" />{{ __('Print') }}</button>
            </p>
            <x-site.updated :at="$groups->max('updated_at') ?? $page->updated_at" class="mt-6 text-center" />
        @endif

        <x-site.page-neighbours :page="$page" class="mt-12" />
    </div>
</x-layouts.site>
