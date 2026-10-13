{{-- 403: a page the visitor may not open (resources/views/errors/page.blade.php draws it). --}}
@include('errors.page', ['code' => 403, 'title' => __('This page is not for you to open'), 'message' => __('It is kept for staff who are signed in, and for some of them only.')])
