<?php

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function eventStartingIn(int $seconds): Event
{
    return Event::factory()->for(User::factory())->create([
        'created_time' => now()->addSeconds($seconds)->getTimestamp(),
    ]);
}

function registerAttendee(Event $event): Attendee
{
    return Attendee::create([
        'event_id' => $event->id,
        'name' => 'Katherine Johnson',
        'email' => 'katherine@example.com',
        'status' => 'attending',
    ]);
}

it('sends a 3-day reminder for events ~3 days out', function () {
    Mail::fake();

    $attendee = registerAttendee(eventStartingIn(2 * 24 * 3600));

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, fn ($mail) => $mail->lead === 'in 3 days');
    expect($attendee->fresh()->reminder_3d_sent_at)->not->toBeNull();
    expect($attendee->fresh()->reminder_24h_sent_at)->toBeNull();
});

it('sends a 24-hour reminder for events within a day', function () {
    Mail::fake();

    $attendee = registerAttendee(eventStartingIn(12 * 3600));

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, fn ($mail) => $mail->lead === 'tomorrow');
    expect($attendee->fresh()->reminder_24h_sent_at)->not->toBeNull();
});

it('does not send reminders for distant events', function () {
    Mail::fake();

    registerAttendee(eventStartingIn(10 * 24 * 3600));

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('does not send a reminder twice', function () {
    Mail::fake();

    registerAttendee(eventStartingIn(2 * 24 * 3600));

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminderMail::class, 1);
});
