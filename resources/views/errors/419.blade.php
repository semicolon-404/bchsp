{{-- 419: a form sent after its time ran out (resources/views/errors/page.blade.php draws it). --}}
@include('errors.page', ['code' => 419, 'title' => __('This page was open too long'), 'message' => __('For safety, a form is only good for a while. Go back, open it again and send it once more.')])
