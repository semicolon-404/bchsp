{{-- 429: too many requests from one place (resources/views/errors/page.blade.php draws it). --}}
@include('errors.page', ['code' => 429, 'title' => __('Too many requests at once'), 'message' => __('Wait a moment, then try again.')])
