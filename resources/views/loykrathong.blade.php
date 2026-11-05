{{--
    /loykrathong: the river at night under a full moon, its light laid on the water, lanterns rising far off, every
    krathong floated this festival drifting on it (the departments' and the visitors'), and a card to float one's own:
    a krathong chosen from four, a name, a wish. Floated, it goes onto the water at once and drifts off with the rest
    (KrathongController). Moves by transform and opacity alone; still for those who ask for less motion.
--}}
@php($fresh = $mine ? $krathongs->firstWhere('id', $mine) : null)
<x-layouts.site :title="__('Float a krathong online')" :description="__(':name invites you to float a krathong online and make a wish.', ['name' => App\Support\SiteSettings::name()])">
    <section class="loy">
        {{-- The sky: stars, the moon, lanterns far off. --}}
        <div class="loy-sky" aria-hidden="true">
            @for ($i = 0; $i < 40; $i++)
                <span class="loy-star" style="left: {{ ($i * 37) % 100 }}%; top: {{ ($i * 53) % 55 }}%; --wait: -{{ ($i * 7) % 5 }}s"></span>
            @endfor
            <span class="loy-moon"></span>
            @for ($i = 0; $i < 7; $i++)
                <span class="loy-lantern" style="left: {{ 8 + $i * 13 }}%; --time: {{ 26 + ($i * 7) % 14 }}s; --wait: -{{ ($i * 11) % 30 }}s"></span>
            @endfor
        </div>

        <div class="wrap relative z-[2] grid items-start gap-8 py-8 sm:py-12 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="text-white">
                <p class="eyebrow text-amber-200/90">{{ $festival->name }}</p>
                <h1 class="mt-2 text-4xl font-semibold leading-tight sm:text-5xl">{{ __('Float a krathong online') }}</h1>
                <p class="mt-3 max-w-xl text-lg text-white/80">{{ __('Choose a krathong, write a wish for yourself, your family or the people of Buached, and set it on the river.') }}</p>
                <p class="mt-5 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm text-white/90"><span class="size-2 rounded-full bg-amber-300" aria-hidden="true"></span>{{ trans_choice('{0} Be the first on the river|[1,*] :count krathongs on the river', $floated, ['count' => number_format($floated)]) }}</p>

                @if ($fresh)
                    <p class="loy-thanks mt-6" role="status">{{ __('Your krathong is on the river. May your wish come true.') }}</p>
                @endif
            </div>

            {{-- The card to float one's own. --}}
            <form method="POST" action="{{ route('loykrathong.store') }}" class="loy-card" novalidate>
                @csrf
                {{-- Not for people: a robot that fills every field fills this one, and is turned away. --}}
                <div class="sr-only" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label></div>

                <fieldset>
                    <legend class="text-sm font-semibold">{{ __('Your krathong') }}</legend>
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        @foreach (App\Models\Krathong::STYLES as $style => $label)
                            <label class="loy-pick">
                                <input type="radio" name="style" value="{{ $style }}" class="sr-only" @checked(old('style', 'banana') === $style)>
                                <span class="krathong is-{{ $style }} is-still"><x-site.krathong-drawing /></span>
                                <span class="text-xs">{{ __($label) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-4 block">
                    <span class="text-sm font-semibold">{{ __('Your name') }}</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="40" required placeholder="{{ __('e.g. Somchai, the Saetang family') }}" class="loy-input">
                </label>
                <label class="mt-3 block">
                    <span class="text-sm font-semibold">{{ __('Your wish') }}</span>
                    <textarea name="wish" maxlength="140" rows="3" required placeholder="{{ __('Good health to everyone in Buached') }}" class="loy-input">{{ old('wish') }}</textarea>
                </label>
                @if ($errors->any())
                    <p class="mt-2 text-sm font-semibold text-red-600" role="alert">{{ $errors->first() }}</p>
                @endif
                <button class="btn btn-brand mt-4 w-full justify-center"><x-heroicon-s-sparkles class="size-5" aria-hidden="true" />{{ __('Float it') }}</button>
                <p class="mt-2 text-xs text-muted">{{ __('Shown on the river for everyone to see. Kind words, please.') }}</p>
            </form>
        </div>

        {{-- The river: the moon's light laid on it, waves, and the krathongs. --}}
        <div class="loy-river">
            <span class="loy-glint" aria-hidden="true"></span>
            <span class="river-wave" aria-hidden="true"></span><span class="river-wave is-back" aria-hidden="true"></span>
            <ul class="loy-krathongs">
                @foreach ($krathongs as $one)
                    <li @class(['krathong is-'.$one->style, 'is-mine' => $one->id === $mine]) style="--row: {{ $loop->index % 3 }}; --time: {{ 50 + ($loop->index * 17) % 40 }}s; --wait: -{{ ($loop->index * 13) % 80 }}s">
                        <button type="button" class="krathong-tap" aria-label="{{ $one->by().': '.$one->wish }}"><x-site.krathong-drawing /></button>
                        <span class="krathong-wish" role="tooltip"><b>{{ $one->by() }}</b>{{ $one->wish }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.site>
