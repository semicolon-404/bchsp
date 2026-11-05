{{-- One popup's face (components/site/site-popups): its picture or a band of its colour, title, text, buttons. --}}
@if ($image = $popup->imageUrl())
    <img src="{{ $image }}" alt="" class="site-popup-picture" loading="lazy">
@else
    <span class="site-popup-band" aria-hidden="true"></span>
@endif
<div class="site-popup-body">
    <h2 class="site-popup-title">{{ $popup->title }}</h2>
    @if (filled($popup->body))
        <div class="rich site-popup-text mt-2">{!! $popup->renderedBody() !!}</div>
    @endif
    @if (($popup->button_label && $popup->button_url) || ($popup->second_label && $popup->second_url))
        <p class="site-popup-actions">
            @if ($popup->button_label && $popup->button_url)
                <a href="{{ $popup->button_url }}" class="site-popup-main" @if (str_starts_with($popup->button_url, 'http')) target="_blank" rel="noopener" @endif>{{ $popup->button_label }}</a>
            @endif
            @if ($popup->second_label && $popup->second_url)
                <a href="{{ $popup->second_url }}" class="btn btn-line" @if (str_starts_with($popup->second_url, 'http')) target="_blank" rel="noopener" @endif>{{ $popup->second_label }}</a>
            @endif
        </p>
    @endif
</div>
