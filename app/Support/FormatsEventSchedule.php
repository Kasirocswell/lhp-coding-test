<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * Shared helpers for turning an event's raw unix start time + coordinates into
 * a friendly, timezone-aware string for emails.
 */
trait FormatsEventSchedule
{
    protected function eventTitle(Event $event): string
    {
        return $event->payload['name'] ?? 'your event';
    }

    protected function eventWhen(Event $event): string
    {
        $location = app(ReverseGeocoder::class)->lookup($event->latitude, $event->longitude);

        $start = Carbon::createFromTimestamp((int) $event->created_time, $location['timezone']);

        // e.g. "Sat, Jun 21, 2026 at 8:00 PM (Europe/Berlin)"
        return $start->format('D, M j, Y \a\t g:i A')." ({$location['timezone']})";
    }

    protected function eventLocation(Event $event): string
    {
        $location = app(ReverseGeocoder::class)->lookup($event->latitude, $event->longitude);
        $venue = $event->payload['venue']['name'] ?? null;

        return $venue ? "{$venue}, {$location['label']}" : $location['label'];
    }
}
