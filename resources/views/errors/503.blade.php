{{-- 503: the site closed for work on it (resources/views/errors/page.blade.php draws it). --}}
@include('errors.page', ['code' => 503, 'title' => __('Back soon'), 'message' => __('The website is being worked on and will be back shortly.'), 'ways' => false])
