{{-- App\Mail\SystemProblems: plain text, one block for each thing that is wrong. --}}
{{ __('The website\'s daily check found :count thing(s) that need looking at:', ['count' => count($problems)]) }}

@foreach ($problems as $problem)
- {{ __($problem['name']) }}: {{ $problem['found'] }}
@if ($problem['advice'])
  {{ $problem['advice'] }}
@endif

@endforeach
{{ __('See them all, and what to do, at:') }} {{ url('/admin/system-status') }}
