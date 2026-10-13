{{-- The QR code of a post, in the panel (EditAnnouncement, "qr"): the picture, and the way to save it. --}}
<div style="display: grid; justify-items: center; gap: 1rem;">
    <img src="{{ $url }}" alt="{{ __('QR code') }}" width="240" height="240" style="background: #fff; border-radius: 0.75rem; padding: 0.5rem;">
    <x-filament::button tag="a" :href="$url.'&download=1'" icon="heroicon-o-arrow-down-tray" color="gray">{{ __('Download (SVG)') }}</x-filament::button>
</div>
