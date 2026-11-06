{{--
    For someone signed in, at the right of the bar: a bell with the count of what is unread and the newest few to open,
    and their round initials opening a menu (who they are, the back office, their profile, sign out). Nothing for a
    visitor. Two small queries, made only for someone signed in. Closed by resources/js/menu.js like the menu's groups.
--}}
@php($user = auth()->user())
@if ($user)
    @php($unread = $user->unreadNotifications()->count())
    @php($latest = $user->notifications()->latest()->limit(5)->get())
    @php($initials = mb_strtoupper(mb_substr(trim($user->name), 0, 1)))

    <details class="menu-group relative shrink-0" data-menu>
        <summary class="account-bell" aria-label="{{ __('Notifications') }}{{ $unread ? ' ('.$unread.')' : '' }}" title="{{ __('Notifications') }}">
            <x-heroicon-o-bell class="size-5" aria-hidden="true" />
            @if ($unread)
                <span class="account-badge" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
        </summary>
        <div class="menu-list account-panel left-auto right-0">
            <p class="account-panel-head">
                <span>{{ __('Notifications') }}</span>
                @if ($unread)
                    <form method="POST" action="{{ route('my-notifications.read') }}">
                        @csrf
                        <button type="submit" class="account-read-all">{{ __('Mark all as read') }}</button>
                    </form>
                @endif
            </p>
            @forelse ($latest as $one)
                <a href="{{ route('my-notifications.open', $one->id) }}" @class(['account-note', 'is-unread' => ! $one->read_at])>
                    <span class="account-note-title">{{ $one->data['title'] ?? __('Notification') }}</span>
                    <span class="text-xs text-muted">{{ $one->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-muted">{{ __('Nothing new') }}</p>
            @endforelse
            <a href="{{ route('filament.admin.pages.notifications') }}" class="account-panel-foot">{{ __('See all') }} &rarr;</a>
        </div>
    </details>

    <details class="menu-group relative shrink-0" data-menu>
        <summary class="account-me" aria-label="{{ $user->name }}" title="{{ $user->name }}">{{ $initials }}</summary>
        <div class="menu-list account-panel left-auto right-0">
            {{-- Who one is: a press opens one's profile. --}}
            <a href="{{ route('filament.admin.auth.profile') }}" class="account-who" title="{{ __('Profile') }}">
                <span class="account-me is-large" aria-hidden="true">{{ $initials }}</span>
                <span class="min-w-0">
                    <span class="block truncate font-semibold">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-muted">{{ $user->department?->localName() ?? ($user->isAdmin() ? __('Administrator') : $user->email) }}</span>
                </span>
            </a>
            {{-- Language and look, folded away behind the gear at the top right of the menu. --}}
            <details class="account-settings">
                <summary class="account-gear" aria-label="{{ __('Language and display') }}" title="{{ __('Language and display') }}"><x-heroicon-o-cog-6-tooth class="size-5" aria-hidden="true" /></summary>
            <div class="account-prefs">
                <div class="account-pref"><span><x-heroicon-o-language class="size-5" aria-hidden="true" />{{ __('Language') }}</span><x-site.language-switch class="slide-switch" /></div>
                <div class="account-pref"><span><x-heroicon-o-swatch class="size-5" aria-hidden="true" />{{ __('Theme') }}</span><x-site.theme-switch class="slide-switch" /></div>
            </div>
            </details>
            <a href="{{ url('/admin') }}" class="account-item"><x-heroicon-o-squares-2x2 class="size-5" aria-hidden="true" />{{ __('Back office') }}</a>
            <a href="{{ route('filament.admin.auth.profile') }}" class="account-item"><x-heroicon-o-user-circle class="size-5" aria-hidden="true" />{{ __('Profile') }}</a>
            <a href="{{ route('filament.admin.pages.notifications') }}" class="account-item"><x-heroicon-o-bell class="size-5" aria-hidden="true" />{{ __('Notifications') }}</a>
            <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="border-t border-line">
                @csrf
                <button type="submit" class="account-item is-out w-full"><x-heroicon-o-arrow-right-on-rectangle class="size-5" aria-hidden="true" />{{ __('Sign out') }}</button>
            </form>
        </div>
    </details>
@endif
