<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceConfirmationMail;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AttendeeController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['nullable', 'in:interested,attending'],
        ]);

        $attendee = Attendee::firstOrNew([
            'event_id' => $event->id,
            'email' => $validated['email'],
        ]);

        if (! $attendee->exists) {
            $attendee->fill([
                'name' => $validated['name'],
                'status' => $validated['status'] ?? 'interested',
            ])->save();

            Mail::to($attendee->email)->queue(new AttendanceConfirmationMail($attendee, $event));

            $attendee->forceFill(['confirmation_sent_at' => now()])->save();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "You're on the list — we've emailed you a confirmation.",
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => "You're already registered for this event.",
            ]);
        }

        return back();
    }
}
