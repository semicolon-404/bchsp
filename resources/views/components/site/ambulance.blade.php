{{--
    An ambulance driving slowly along the pulse at the foot of the home page's first screen, over its beat, its lights
    going and thrown over the screen; every half minute it comes by again. How bright the lights are is the
    administrator's choice (Site settings > The hospital): low, medium, bright or very bright.

    <x-site.ambulance light="medium" />
--}}
@props(['light' => 'medium'])

{{-- The lights' red and blue thrown over the whole first screen, the sign too, strongest where the van is. --}}
<div class="hero-siren is-{{ $light }}" aria-hidden="true"></div>
<div class="ambulance-road" aria-hidden="true">
    <div class="ambulance">
        <span class="ambulance-beam"></span>
        <span class="ambulance-glow"></span>
        {{-- A Thai high-roof ambulance van, seen from the side: the yellow and green squares along it, the blue star of life, 1669. --}}
        <svg viewBox="0 0 120 60" class="ambulance-body">
            <rect x="22" y="2" width="30" height="5" rx="2" class="ambulance-bar" />
            <rect x="22" y="2" width="15" height="5" rx="2" class="ambulance-red" />
            <rect x="37" y="2" width="15" height="5" rx="2" class="ambulance-blue" />
            <path d="M6 8h80q4 0 6 4l12 14q10 2 12 8v12H6z" fill="#fff" stroke="#cbd5e1" stroke-width="1.2" />
            <path d="M88 13l11 13H88z" fill="#1e293b" opacity="0.85" />
            <rect x="54" y="13" width="28" height="11" rx="1.5" fill="#1e293b" opacity="0.85" />
            <rect x="104" y="26" width="12" height="17" fill="#facc15" />
            <rect x="8" y="31" width="7" height="6" fill="#facc15" /><rect x="8" y="37" width="7" height="6" fill="#16a34a" /><rect x="15" y="31" width="7" height="6" fill="#16a34a" /><rect x="15" y="37" width="7" height="6" fill="#facc15" /><rect x="22" y="31" width="7" height="6" fill="#facc15" /><rect x="22" y="37" width="7" height="6" fill="#16a34a" /><rect x="29" y="31" width="7" height="6" fill="#16a34a" /><rect x="29" y="37" width="7" height="6" fill="#facc15" /><rect x="36" y="31" width="7" height="6" fill="#facc15" /><rect x="36" y="37" width="7" height="6" fill="#16a34a" /><rect x="43" y="31" width="7" height="6" fill="#16a34a" /><rect x="43" y="37" width="7" height="6" fill="#facc15" /><rect x="50" y="31" width="7" height="6" fill="#facc15" /><rect x="50" y="37" width="7" height="6" fill="#16a34a" /><rect x="57" y="31" width="7" height="6" fill="#16a34a" /><rect x="57" y="37" width="7" height="6" fill="#facc15" /><rect x="64" y="31" width="7" height="6" fill="#facc15" /><rect x="64" y="37" width="7" height="6" fill="#16a34a" /><rect x="71" y="31" width="7" height="6" fill="#16a34a" /><rect x="71" y="37" width="7" height="6" fill="#facc15" /><rect x="78" y="31" width="7" height="6" fill="#facc15" /><rect x="78" y="37" width="7" height="6" fill="#16a34a" /><rect x="85" y="31" width="7" height="6" fill="#16a34a" /><rect x="85" y="37" width="7" height="6" fill="#facc15" /><rect x="92" y="31" width="7" height="6" fill="#facc15" /><rect x="92" y="37" width="7" height="6" fill="#16a34a" /><rect x="99" y="31" width="7" height="6" fill="#16a34a" /><rect x="99" y="37" width="7" height="6" fill="#facc15" />
            <rect x="6" y="30" width="110" height="1.2" fill="#16a34a" />
            <g transform="translate(36 21)"><path d="M0 -6v12M-5.2 -3l10.4 6M-5.2 3l10.4 -6" stroke="#2563eb" stroke-width="3.2" stroke-linecap="round" /><path d="M0 -4v8" stroke="#fff" stroke-width="0.8" /></g>
            <text x="66" y="29.5" font-size="5.5" font-weight="700" fill="#16a34a" font-family="sans-serif">1669</text>
            <rect x="113" y="33" width="4" height="3" rx="1" fill="#fde68a" />
            <rect x="4" y="43" width="114" height="3" rx="1.5" fill="#94a3b8" />
            <g class="ambulance-wheel" style="transform-origin: 26px 49px"><circle cx="26" cy="49" r="8" fill="#1f2937" /><circle cx="26" cy="49" r="3.5" fill="#cbd5e1" /><path d="M26 42v14M19 49h14" stroke="#64748b" stroke-width="1.2" /></g>
            <g class="ambulance-wheel" style="transform-origin: 96px 49px"><circle cx="96" cy="49" r="8" fill="#1f2937" /><circle cx="96" cy="49" r="3.5" fill="#cbd5e1" /><path d="M96 42v14M89 49h14" stroke="#64748b" stroke-width="1.2" /></g>
        </svg>
    </div>
</div>
