<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_id
 * @property string $name
 * @property string $email
 * @property string $status
 * @property Carbon|null $confirmation_sent_at
 * @property Carbon|null $reminder_3d_sent_at
 * @property Carbon|null $reminder_24h_sent_at
 */
class Attendee extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'status',
    ];

    protected $casts = [
        'confirmation_sent_at' => 'datetime',
        'reminder_3d_sent_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
