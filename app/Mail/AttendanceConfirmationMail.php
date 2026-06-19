<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use App\Support\FormatsEventSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceConfirmationMail extends Mailable implements ShouldQueue
{
    use FormatsEventSchedule, Queueable, SerializesModels;

    public function __construct(
        public Attendee $attendee,
        public Event $event,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're on the list: ".$this->eventTitle($this->event),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.attendance-confirmation',
            with: [
                'attendeeName' => $this->attendee->name,
                'eventTitle' => $this->eventTitle($this->event),
                'when' => $this->eventWhen($this->event),
                'location' => $this->eventLocation($this->event),
                'url' => route('events.show', $this->event),
            ],
        );
    }
}
