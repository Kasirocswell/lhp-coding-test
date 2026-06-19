<?php

namespace App\Console\Commands;

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Email attendees reminders 3 days and 24 hours before their event.';

    public function handle(): int
    {
        $now = Carbon::now();
        $in24h = $now->copy()->addDay()->getTimestamp();
        $in3d = $now->copy()->addDays(3)->getTimestamp();

        // 3-day reminder: event is more than 24h but within 3 days away. The
        // 24h lower bound prevents last-minute registrants getting both
        // reminders at once.
        $threeDay = $this->send(
            Attendee::query()
                ->whereNull('reminder_3d_sent_at')
                ->whereHas('event', fn (Builder $q) => $q
                    ->where('created_time', '>', $in24h)
                    ->where('created_time', '<=', $in3d)),
            lead: 'in 3 days',
            column: 'reminder_3d_sent_at',
        );

        // 24-hour reminder: event is within the next 24 hours.
        $oneDay = $this->send(
            Attendee::query()
                ->whereNull('reminder_24h_sent_at')
                ->whereHas('event', fn (Builder $q) => $q
                    ->where('created_time', '>', $now->getTimestamp())
                    ->where('created_time', '<=', $in24h)),
            lead: 'tomorrow',
            column: 'reminder_24h_sent_at',
        );

        $this->info("Queued {$threeDay} 3-day and {$oneDay} 24-hour reminder(s).");

        return self::SUCCESS;
    }

    /**
     * @param  Builder<Attendee>  $query
     */
    private function send(Builder $query, string $lead, string $column): int
    {
        $sent = 0;

        $query->with('event')->chunkById(200, function ($attendees) use ($lead, $column, &$sent) {
            foreach ($attendees as $attendee) {
                if ($attendee->event === null) {
                    continue;
                }

                Mail::to($attendee->email)->queue(new EventReminderMail($attendee, $attendee->event, $lead));
                $attendee->forceFill([$column => now()])->save();
                $sent++;
            }
        });

        return $sent;
    }
}
