<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEvent(array $attributes = []): Event
{
    return Event::factory()->for(User::factory())->create($attributes);
}

it('renders the events listing shell with filter options', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('filterOptions.cities')
            ->has('filterOptions.types', 8)
            ->has('filterOptions.statuses', 4)
        );
});

it('returns an enriched, paginated page of events', function () {
    makeEvent([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'payload' => [
            'name' => 'Synthwave Night',
            'description' => 'A great show',
            'venue' => ['name' => 'The Grand Hall'],
            'schedule' => ['starts_at' => 1_700_000_000, 'ends_at' => 1_700_007_200],
            'pricing' => ['currency' => 'USD', 'min_price' => 42.5],
        ],
    ]);

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'description', 'type', 'status', 'starts_at', 'timezone', 'location', 'price', 'images']],
            'current_page',
            'last_page',
            'per_page',
            'total',
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.title', 'Synthwave Night')
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.location', 'New York, United States')
        ->assertJsonPath('data.0.timezone', 'America/New_York');
});

it('serves event images locally', function () {
    makeEvent(['type' => 'festival']);

    $response = $this->getJson(route('events.data'));
    $images = $response->json('data.0.images');

    expect($images)->toHaveCount(3);
    expect($images[0])->toStartWith('/images/events/festival/');
});

it('filters the data endpoint by status', function () {
    makeEvent(['status' => 'published']);
    makeEvent(['status' => 'cancelled']);

    $this->getJson(route('events.data', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'cancelled');
});

it('filters the data endpoint by date range', function () {
    makeEvent(['created_time' => strtotime('2025-06-15 12:00:00 UTC')]);
    makeEvent(['created_time' => strtotime('2025-08-15 12:00:00 UTC')]);

    $this->getJson(route('events.data', ['from' => '2025-08-01', 'to' => '2025-08-31']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('filters the data endpoint by city', function () {
    makeEvent(['latitude' => 51.5074, 'longitude' => -0.1278]); // London
    makeEvent(['latitude' => 35.6762, 'longitude' => 139.6503]); // Tokyo

    $this->getJson(route('events.data', ['city' => 'london-united-kingdom']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.city', 'London');
});

it('shows an enriched event detail page', function () {
    $event = makeEvent([
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'payload' => ['name' => 'Global Tech Summit', 'venue' => ['name' => 'Expo Center']],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.title', 'Global Tech Summit')
            ->where('event.location', 'Paris, France')
            ->has('event.images', 3)
            ->has('attendees')
        );
});

it('renders the two visualization pages and the dashboard without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
    $this->get(route('dashboard'))->assertOk();
});
