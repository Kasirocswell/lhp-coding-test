@component('mail::message')
# You're on the list, {{ $attendeeName }}!

Thanks for registering your interest in **{{ $eventTitle }}**. We've saved your spot.

**When:** {{ $when }}
**Where:** {{ $location }}

@component('mail::button', ['url' => $url])
View event details
@endcomponent

We'll send you a reminder as the event approaches. See you there!

Thanks,
{{ config('app.name') }}
@endcomponent
