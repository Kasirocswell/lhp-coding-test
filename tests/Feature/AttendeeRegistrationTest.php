<?php

use App\Mail\AttendanceConfirmationMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('registers an attendee and sends a confirmation email', function () {
    Mail::fake();

    $event = Event::factory()->for(User::factory())->create();

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'status' => 'attending',
    ])->assertRedirect();

    $this->assertDatabaseHas('attendees', [
        'event_id' => $event->id,
        'email' => 'grace@example.com',
        'status' => 'attending',
    ]);

    expect(Attendee::first()->confirmation_sent_at)->not->toBeNull();

    Mail::assertQueued(AttendanceConfirmationMail::class, fn ($mail) => $mail->hasTo('grace@example.com'));
});

it('validates attendee input', function () {
    $event = Event::factory()->for(User::factory())->create();

    $this->post(route('events.attendees.store', $event), ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);
});

it('does not register the same email twice or re-send confirmation', function () {
    Mail::fake();

    $event = Event::factory()->for(User::factory())->create();
    $payload = ['name' => 'Alan Turing', 'email' => 'alan@example.com'];

    $this->post(route('events.attendees.store', $event), $payload);
    $this->post(route('events.attendees.store', $event), $payload);

    expect(Attendee::where('event_id', $event->id)->count())->toBe(1);
    Mail::assertQueued(AttendanceConfirmationMail::class, 1);
});
