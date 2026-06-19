<?php

namespace App\Support;

use App\Models\Event;

/**
 * Turns raw Event rows into the lean, enriched shape the frontend consumes:
 * a real title/description, locally-served images, a human-readable address
 * and an IANA timezone so times can be shown sensibly.
 */
class EventPresenter
{
    /** Image variants available per category on disk. */
    public const IMAGE_VARIANTS = 3;

    /** How many images to expose per event (the task asks for two or more). */
    public const IMAGES_PER_EVENT = 3;

    public function __construct(private readonly ReverseGeocoder $geocoder) {}

    /**
     * Shape used by the listing endpoint (gallery + timeline cards).
     *
     * Expects an Event hydrated with json_extract aliases (see
     * EventController::loadListing) rather than the full payload, so we never
     * ship the heavy `notes` padding over the wire.
     *
     * @return array<string, mixed>
     */
    public function forList(Event $event): array
    {
        $location = $this->geocoder->lookup($event->latitude, $event->longitude);

        return [
            'id' => $event->id,
            'title' => $event->getAttribute('name') ?? 'Untitled event',
            'description' => $event->getAttribute('description'),
            'type' => $event->type,
            'status' => $event->status,
            'starts_at' => $this->toInt($event->created_time),
            'ends_at' => $this->toInt($event->getAttribute('ends_at')),
            'timezone' => $location['timezone'],
            'venue' => $event->getAttribute('venue_name'),
            'location' => $location['label'],
            'city' => $location['city'],
            'country' => $location['country'],
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'price' => $this->toFloat($event->getAttribute('min_price')),
            'currency' => $event->getAttribute('currency') ?? 'USD',
            'attendees_count' => (int) ($event->getAttribute('attendees_count') ?? 0),
            'image' => $this->imagesFor($event->id, $event->type)[0],
            'images' => $this->imagesFor($event->id, $event->type),
        ];
    }

    /**
     * Full shape used by the event detail page.
     *
     * @return array<string, mixed>
     */
    public function forDetail(Event $event): array
    {
        $payload = $event->payload ?? [];
        $location = $this->geocoder->lookup($event->latitude, $event->longitude);

        return [
            'id' => $event->id,
            'title' => $payload['name'] ?? 'Untitled event',
            'description' => $payload['description'] ?? null,
            'type' => $event->type,
            'status' => $event->status,
            'starts_at' => $this->toInt($payload['schedule']['starts_at'] ?? $event->created_time),
            'ends_at' => $this->toInt($payload['schedule']['ends_at'] ?? null),
            'timezone' => $location['timezone'],
            'venue' => $payload['venue']['name'] ?? null,
            'capacity' => isset($payload['venue']['capacity']) ? (int) $payload['venue']['capacity'] : null,
            'organizer' => $payload['organizer']['name'] ?? null,
            'location' => $location['label'],
            'city' => $location['city'],
            'country' => $location['country'],
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'price' => $this->toFloat($payload['pricing']['min_price'] ?? null),
            'currency' => $payload['pricing']['currency'] ?? 'USD',
            'tags' => $payload['tags'] ?? [],
            'images' => $this->imagesFor($event->id, $event->type),
            'attendees_count' => (int) ($event->getAttribute('attendees_count') ?? 0),
        ];
    }

    /**
     * Deterministically assign a stable set of locally-served images to an
     * event. The starting variant is derived from the event id so the same
     * event always shows the same images, while different events vary.
     *
     * @return list<string>
     */
    public function imagesFor(string $id, string $type): array
    {
        $start = crc32($id) % self::IMAGE_VARIANTS;
        $images = [];

        for ($i = 0; $i < self::IMAGES_PER_EVENT; $i++) {
            $variant = (($start + $i) % self::IMAGE_VARIANTS) + 1;
            $images[] = "/images/events/{$type}/{$variant}.svg";
        }

        return $images;
    }

    private function toInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function toFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
