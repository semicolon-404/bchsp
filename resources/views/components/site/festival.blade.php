{{--
    The festival on today (App\Models\Festival), along the foot of the home page's first screen in place of the
    pulse. Loy Krathong: a river, the krathongs drifting on it, each with its wish on a press. The other days: a few
    things falling or rising in the day's colours (App\Models\Festival::LOOKS).
    Everything moves by transform and opacity alone (no blur, no repainting), and is still for those who ask for less.

    <x-site.festival :festival="$festival" />
--}}
@props(['festival'])
@php
    $count = $festival->pieces();
    // Spread evenly, each its own pace and start, so they never move together.
    $spread = fn (int $i) => ['left' => round(($i + 0.5) * 100 / $count, 1), 'time' => 9 + ($i * 7) % 9, 'wait' => -(($i * 3.7) % 12)];
@endphp

<div class="festival is-{{ $festival->theme }}" aria-label="{{ $festival->name }}" role="img">
    @if ($festival->theme === 'loykrathong')
        @php($krathongs = $festival->krathongs()->where('is_hidden', false)->with('department')->limit(12)->get())
        {{-- The way to float one's own. --}}
        <a href="{{ route('loykrathong') }}" class="festival-go">{{ __('Float a krathong online') }} &rarr;</a>
        <span class="festival-moon" aria-hidden="true"></span>
        <div class="river" aria-hidden="true"><span class="river-wave"></span><span class="river-wave is-back"></span></div>
        <ul class="krathongs">
            {{-- The departments' own, then plain ones to keep the river full. --}}
            @foreach ($krathongs->concat(array_fill(0, max(0, min(12, $count) - $krathongs->count()), null)) as $one)
                @php($at = $spread($loop->index))
                <li class="krathong is-{{ $one?->style ?? ['banana', 'lotus', 'flower', 'bread'][$loop->index % 4] }}" style="--start: {{ $at['left'] }}%; --time: {{ 40 + ($loop->index * 13) % 30 }}s; --wait: {{ -($loop->index * 9) % 50 }}s">
                    @if ($one)
                        <button type="button" class="krathong-tap" aria-label="{{ $one->by().': '.$one->wish }}">
                            <x-site.krathong-drawing />
                        </button>
                        <span class="krathong-wish" role="tooltip"><b>{{ $one->by() }}</b>{{ $one->wish }}</span>
                    @else
                        <x-site.krathong-drawing />
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        @php([$look, $colours] = $festival->look())
        <ul class="festival-fall is-{{ $look }}" aria-hidden="true">
            @for ($i = 0; $i < $count; $i++)
                @php($at = $spread($i))
                <li style="left: {{ $at['left'] }}%; --time: {{ $at['time'] }}s; --wait: {{ $at['wait'] }}s; --size: {{ 0.8 + ($i % 4) * 0.25 }}rem; --c: {{ $colours[$i % count($colours)] }}"></li>
            @endfor
        </ul>
    @endif
</div>
