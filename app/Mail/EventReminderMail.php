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

class EventReminderMail extends Mailable implements ShouldQueue
{
    use FormatsEventSchedule, Queueable, SerializesModels;

    /**
     * @param  string  $lead  Human-readable lead time, e.g. "in 3 days" or "tomorrow".
     */
    public function __construct(
        public Attendee $attendee,
        public Event $event,
        public string $lead,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: '.$this->eventTitle($this->event)." is {$this->lead}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.event-reminder',
            with: [
                'attendeeName' => $this->attendee->name,
                'eventTitle' => $this->eventTitle($this->event),
                'lead' => $this->lead,
                'when' => $this->eventWhen($this->event),
                'location' => $this->eventLocation($this->event),
                'url' => route('events.show', $this->event),
            ],
        );
    }
}
