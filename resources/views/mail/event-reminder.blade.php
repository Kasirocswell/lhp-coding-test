@component('mail::message')
# {{ $eventTitle }} is {{ $lead }}

Hi {{ $attendeeName }}, this is a friendly reminder that you're registered for **{{ $eventTitle }}**.

**When:** {{ $when }}
**Where:** {{ $location }}

@component('mail::button', ['url' => $url])
View event details
@endcomponent

Looking forward to seeing you there!

Thanks,
{{ config('app.name') }}
@endcomponent
