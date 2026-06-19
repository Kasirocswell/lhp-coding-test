# Event Visuals — Implementation Notes

A short tour of what I built and the decisions behind it.

## The two pages (two distinct layouts)

- **Visual 1 — Gallery** (`/events-visual-1`): an image-forward responsive card
  grid. Cards crossfade to a second image on hover, lift on hover, and fade/slide
  in with a small stagger. This is the "browse by vibe" view.
- **Visual 2 — Timeline** (`/events-visual-2`): a chronological agenda. Events are
  grouped by day with sticky date headers and laid out along a vertical rail with
  dots and time stamps. This is the "what's on, and when" view.

Both share one data source, one filter bar and one formatting layer, but the
layouts are deliberately different (spatial grid vs. temporal rail).

## Data & enrichment

The events table keeps almost everything in a JSON `payload`. Rather than reshape
1M+ rows, I enrich at read time:

- **`EventPresenter`** projects the raw row into a lean, typed shape. The listing
  query uses SQLite `json_extract` to pull only the fields it needs (title, venue,
  price, …) so the heavy `notes` padding never goes over the wire.
- **Images** — events had none. I generate a small set of locally-served gradient
  SVGs per category (`php artisan images:generate-placeholders` → `public/images/events/{type}/{1,2,3}.svg`) and assign each event a stable set of 3 images
  deterministically from its id. This satisfies "two or more images per event,
  served locally" without adding a 3M-row image table. Image generation is a
  repeatable artisan command, so it's end-to-end and reproducible.
- **Addresses** — events only carry lat/lng. `ReverseGeocoder` maps each coordinate
  to its nearest known city (the dataset is jittered around ~80 real city anchors),
  giving a human-readable "City, Country" plus an IANA timezone — fully offline, no
  external geocoding API for a million rows.

## Date, time & timezones

Events are global, so "8 PM" should mean 8 PM *at the venue*. Each event carries
the timezone resolved from its coordinates; the frontend formats times in that zone
with `Intl.DateTimeFormat`. The detail page also shows the viewer's local time when
it differs, so you always know both.

## Filtering

The `/events/data` endpoint supports search, **date range (from/to)**,
**location (city)**, category, status and sort, with cursor-style infinite scroll.
Date filters hit an index on `created_time`; the city filter resolves to a lat/lng
bounding box backed by a `(latitude, longitude)` index (both added in a migration)
to stay fast on the full dataset. The gallery defaults to upcoming events
(`from = today`, soonest first).

## Attendees & emails

- `attendees` table (unique per event+email) with an `AttendeeController` and the
  RSVP form on the detail page (interested / attending).
- On registration we queue an **`AttendanceConfirmationMail`**.
- **`events:send-reminders`** is scheduled hourly (`routes/console.php`) and queues
  **3-day** and **24-hour** reminders. Sent-at columns make it idempotent, and the
  3-day window starts at >24h out so a last-minute registrant doesn't get both at
  once.
- Mail is sent through **Resend** (`MAIL_MAILER=resend`, `RESEND_API_KEY`) over the
  database queue (the `composer dev` script runs a worker). Set a verified domain
  as `MAIL_FROM_ADDRESS` to reach arbitrary recipients; `onboarding@resend.dev`
  works for testing, and `MAIL_MAILER=log` writes emails to the log for offline dev.

## Performance for the seeded scale

The default seed is ~1.25M rows. Choices that keep it responsive: `json_extract`
projection (no payload bloat), indexes on `created_time` and `(latitude, longitude)`,
paginated/infinite-scroll fetching, and a stale-response guard in the feed composable
so rapid filter changes don't race.

## Running it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
SEED_ROWS=4000 php artisan migrate --seed   # smaller seed for fast local dev
php artisan images:generate-placeholders
composer dev                                # serve + queue worker + vite
```

Visit `/` (redirects to the gallery). Tests: `php artisan test`.

## Notes / trade-offs

- Reverse geocoding is nearest-anchor, which is exact for this dataset's generation
  but approximate in general; a real deployment would swap in a proper geocoder
  behind the same `ReverseGeocoder` interface.
- Name search uses `LIKE` on extracted JSON (no index); it's bounded by the date
  filter in practice. A dedicated `name` column / FTS index would be the next step
  if free-text search became hot.
- The pre-existing `EventSeeder`/`EventFactory` carry a few static-analysis findings
  from the starter (intentional `?->` for their perf-test path, `env()` row knob); I
  left them untouched. All code I added passes Pint, PHPStan (level 7), ESLint and
  `vue-tsc`.
