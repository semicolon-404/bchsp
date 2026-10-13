{{-- 404: an address nothing answers to (resources/views/errors/page.blade.php draws it). --}}
@include('errors.page', ['code' => 404, 'title' => __('This page is not here'), 'message' => __('It may have been moved or taken down, or the address was mistyped. Try one of these instead.')])
