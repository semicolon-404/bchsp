{{-- 500: a fault in the site itself (resources/views/errors/page.blade.php draws it). --}}
@include('errors.page', ['code' => 500, 'title' => __('Something went wrong on our side'), 'message' => __('It is not anything you did. It has been written down and will be looked at. Please try again in a little while.'), 'ways' => false])
