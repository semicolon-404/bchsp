{{-- App\Mail\StaffActivityDigest: plain text, one line for each thing done, the time first. --}}
{{ __('What was done in the panel on :day (:count):', ['day' => $day->toDateString(), 'count' => $lines->count()]) }}

@foreach ($lines as $line)
- {{ $line->created_at->format('H:i') }} {{ App\Support\StaffActivity::describe($line) }}
@endforeach

{{ __('The whole access log:') }} {{ App\Filament\Resources\AccessLogs\AccessLogResource::getUrl('index', panel: 'admin') }}
