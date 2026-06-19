<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\EventPresenter;
use App\Support\ReverseGeocoder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** Event categories stored in the `type` column. */
    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    private const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    public function __construct(
        private readonly ReverseGeocoder $geocoder,
        private readonly EventPresenter $presenter,
    ) {}

    /**
     * Legacy reference listing (kept as a lightweight debug view).
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    /**
     * Visual 1 — image-forward card gallery.
     */
    public function visualOne(): Response
    {
        return Inertia::render('Events/VisualOne', [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    /**
     * Visual 2 — chronological timeline / agenda.
     */
    public function visualTwo(): Response
    {
        return Inertia::render('Events/VisualTwo', [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    /**
     * Paginated, enriched JSON feed shared by both visual pages.
     */
    public function data(Request $request): JsonResponse
    {
        $events = $this->loadListing($request);

        return response()->json([
            'data' => collect($events->items())
                ->map(fn (Event $event) => $this->presenter->forList($event))
                ->all(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'per_page' => $events->perPage(),
            'total' => $events->total(),
        ]);
    }

    public function show(Event $event): Response
    {
        $event->loadCount('attendees');

        return Inertia::render('Events/Show', [
            'event' => $this->presenter->forDetail($event),
            'attendees' => $event->attendees()
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn ($attendee) => ['name' => $attendee->name, 'status' => $attendee->status])
                ->all(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Event>
     */
    private function loadListing(Request $request): LengthAwarePaginator
    {
        $perPage = min(max((int) $request->input('per_page', 24), 1), 60);

        $query = Event::query()
            ->select(['id', 'type', 'status', 'created_time', 'latitude', 'longitude'])
            ->selectRaw("json_extract(payload, '$.name') as name")
            ->selectRaw("json_extract(payload, '$.description') as description")
            ->selectRaw("json_extract(payload, '$.venue.name') as venue_name")
            ->selectRaw("json_extract(payload, '$.pricing.min_price') as min_price")
            ->selectRaw("json_extract(payload, '$.pricing.currency') as currency")
            ->selectRaw("json_extract(payload, '$.schedule.ends_at') as ends_at")
            ->withCount('attendees')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('q'), fn ($q) => $q->whereRaw(
                "json_extract(payload, '$.name') like ?",
                ['%'.$request->string('q').'%'],
            ));

        $this->applyDateFilter($query, $request);
        $this->applyLocationFilter($query, $request);

        $direction = $request->input('sort') === 'latest' ? 'desc' : 'asc';
        $query->orderBy('created_time', $direction)->orderBy('id');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyDateFilter(Builder $query, Request $request): void
    {
        if ($from = $this->parseDate($request->input('from'))) {
            $query->where('created_time', '>=', $from->startOfDay()->getTimestamp());
        }

        if ($to = $this->parseDate($request->input('to'))) {
            $query->where('created_time', '<=', $to->endOfDay()->getTimestamp());
        }
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyLocationFilter(Builder $query, Request $request): void
    {
        if (! $request->filled('city')) {
            return;
        }

        $bounds = $this->geocoder->boundsFor((string) $request->string('city'));

        if ($bounds === null) {
            return;
        }

        $query
            ->whereBetween('latitude', [$bounds[0], $bounds[1]])
            ->whereBetween('longitude', [$bounds[2], $bounds[3]]);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{cities: array<int, mixed>, types: list<string>, statuses: list<string>}
     */
    private function filterOptions(): array
    {
        return [
            'cities' => $this->geocoder->cities(),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ];
    }
}
