{{--
    An ambulance driving slowly along the pulse at the foot of the home page's first screen, over its beat, its lights
    going and thrown over the screen; every half minute it comes by again. How bright the lights are is the
    administrator's choice (Site settings > The hospital): low, medium, bright or very bright.

    <x-site.ambulance light="medium" />
--}}
@props(['light' => 'medium'])

{{-- The lights' red and blue thrown over the whole first screen, the sign too, strongest where the van is. --}}
<div class="hero-siren is-{{ $light }}" aria-hidden="true"><span class="siren-red"></span><span class="siren-blue"></span></div>
<div class="ambulance-road" aria-hidden="true">
    <div class="ambulance">
        <span class="ambulance-beam"></span>
        <span class="ambulance-glow"></span>
        {{-- A Thai high-roof ambulance van, seen from the side: white, rounded, its windows dark, the yellow and green
             squares along it, the blue star of life and 1669, the light bar on the roof. --}}
        <svg viewBox="0 0 180 80" class="ambulance-body">
            <defs>
                <linearGradient id="amb-white" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff" /><stop offset="0.75" stop-color="#f1f5f9" /><stop offset="1" stop-color="#cbd5e1" /></linearGradient>
                <linearGradient id="amb-glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#475569" /><stop offset="0.5" stop-color="#1e293b" /><stop offset="1" stop-color="#0f172a" /></linearGradient>
                <clipPath id="amb-side"><path d="M10 14q0-6 6-6h112q6 0 9 4l15 20q16 3 20 10q3 4 3 10v12q0 4-4 4H10z" /></clipPath>
            </defs>
            <rect x="40" y="2" width="40" height="6" rx="3" class="ambulance-bar" />
            <rect x="42" y="3" width="17" height="4" rx="2" class="ambulance-red" />
            <rect x="61" y="3" width="17" height="4" rx="2" class="ambulance-blue" />
            <path d="M10 14q0-6 6-6h112q6 0 9 4l15 20q16 3 20 10q3 4 3 10v12q0 4-4 4H10z" fill="url(#amb-white)" stroke="#94a3b8" stroke-width="1" />
            <g clip-path="url(#amb-side)"><rect x="12" y="42" width="9" height="7" fill="#facc15" /><rect x="12" y="49" width="9" height="7" fill="#15803d" /><rect x="21" y="42" width="9" height="7" fill="#15803d" /><rect x="21" y="49" width="9" height="7" fill="#facc15" /><rect x="30" y="42" width="9" height="7" fill="#facc15" /><rect x="30" y="49" width="9" height="7" fill="#15803d" /><rect x="39" y="42" width="9" height="7" fill="#15803d" /><rect x="39" y="49" width="9" height="7" fill="#facc15" /><rect x="48" y="42" width="9" height="7" fill="#facc15" /><rect x="48" y="49" width="9" height="7" fill="#15803d" /><rect x="57" y="42" width="9" height="7" fill="#15803d" /><rect x="57" y="49" width="9" height="7" fill="#facc15" /><rect x="66" y="42" width="9" height="7" fill="#facc15" /><rect x="66" y="49" width="9" height="7" fill="#15803d" /><rect x="75" y="42" width="9" height="7" fill="#15803d" /><rect x="75" y="49" width="9" height="7" fill="#facc15" /><rect x="84" y="42" width="9" height="7" fill="#facc15" /><rect x="84" y="49" width="9" height="7" fill="#15803d" /><rect x="93" y="42" width="9" height="7" fill="#15803d" /><rect x="93" y="49" width="9" height="7" fill="#facc15" /><rect x="102" y="42" width="9" height="7" fill="#facc15" /><rect x="102" y="49" width="9" height="7" fill="#15803d" /><rect x="111" y="42" width="9" height="7" fill="#15803d" /><rect x="111" y="49" width="9" height="7" fill="#facc15" /><rect x="120" y="42" width="9" height="7" fill="#facc15" /><rect x="120" y="49" width="9" height="7" fill="#15803d" /><rect x="129" y="42" width="9" height="7" fill="#15803d" /><rect x="129" y="49" width="9" height="7" fill="#facc15" /><rect x="10" y="41" width="170" height="1.2" fill="#15803d" /></g>
            <path d="M134 16l13 17h-13z" fill="url(#amb-glass)" />
            <rect x="96" y="16" width="34" height="17" rx="3" fill="url(#amb-glass)" />
            <rect x="60" y="16" width="32" height="17" rx="3" fill="url(#amb-glass)" />
            <path d="M98 18l8 0l-6 12h-4z" fill="#fff" opacity="0.12" />
            <path d="M94 14v52" stroke="#94a3b8" stroke-width="0.8" />
            <path d="M132 14v52" stroke="#94a3b8" stroke-width="0.8" />
            <rect x="136" y="38" width="5" height="1.6" rx="0.8" fill="#64748b" />
            <g transform="translate(34 26)"><circle r="10" fill="#fff" /><path d="M0 -8v16M-6.9 -4l13.8 8M-6.9 4l13.8 -8" stroke="#1d4ed8" stroke-width="4" stroke-linecap="round" /><path d="M0 -5v10" stroke="#fff" stroke-width="1" /></g>
            <text x="100" y="62" font-size="6.5" font-weight="800" fill="#0f172a" font-family="sans-serif">1669</text>
            <path d="M150 34l4 2v4h-4z" fill="#0f172a" />
            <rect x="168" y="44" width="7" height="5" rx="1.5" fill="#fef3c7" stroke="#f59e0b" stroke-width="0.6" />
            <rect x="166" y="58" width="12" height="6" rx="2" fill="#94a3b8" />
            <rect x="6" y="60" width="10" height="6" rx="2" fill="#94a3b8" />
            <rect x="8" y="46" width="3" height="5" rx="1" fill="#ef4444" />
            <g class="ambulance-wheel" style="transform-origin: 38px 66px"><circle cx="38" cy="66" r="11" fill="#111827" /><circle cx="38" cy="66" r="6" fill="#e2e8f0" /><circle cx="38" cy="66" r="2" fill="#64748b" /><path d="M38 60v12M32 66h12" stroke="#94a3b8" stroke-width="1.4" /></g>
            <g class="ambulance-wheel" style="transform-origin: 146px 66px"><circle cx="146" cy="66" r="11" fill="#111827" /><circle cx="146" cy="66" r="6" fill="#e2e8f0" /><circle cx="146" cy="66" r="2" fill="#64748b" /><path d="M146 60v12M140 66h12" stroke="#94a3b8" stroke-width="1.4" /></g>
        </svg>
    </div>
</div>

{{-- The siren, off until the visitor turns it on (resources/js/siren.js): a short wail each time the van comes by. --}}
<button type="button" class="siren-toggle" data-siren aria-pressed="false" title="{{ __('Siren sound') }}">
    <x-heroicon-m-speaker-x-mark class="size-5 siren-off" aria-hidden="true" />
    <x-heroicon-m-speaker-wave class="size-5 siren-on" aria-hidden="true" />
    <span class="sr-only">{{ __('Siren sound') }}</span>
</button>
