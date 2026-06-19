/**
 * Timezone-aware formatting helpers for event times.
 *
 * Events are global, so every event carries its own IANA timezone (resolved
 * server-side from its coordinates). We render times in that local timezone so
 * "8 PM" always means 8 PM at the venue, regardless of where the viewer is.
 */

function toDate(unix: number | null): Date | null {
    return unix === null ? null : new Date(unix * 1000);
}

export function formatDateTime(unix: number | null, timezone: string): string {
    const date = toDate(unix);

    if (!date) {
        return 'Date TBC';
    }

    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZone: timezone,
    }).format(date);
}

export function formatDate(unix: number | null, timezone: string): string {
    const date = toDate(unix);

    if (!date) {
        return 'Date TBC';
    }

    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        timeZone: timezone,
    }).format(date);
}

export function formatTime(unix: number | null, timezone: string): string {
    const date = toDate(unix);

    if (!date) {
        return '';
    }

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        timeZone: timezone,
    }).format(date);
}

/** A stable key (YYYY-MM-DD in the event's timezone) for grouping by day. */
export function dayKey(unix: number | null, timezone: string): string {
    const date = toDate(unix);

    if (!date) {
        return 'tbc';
    }

    return new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        timeZone: timezone,
    }).format(date);
}

export function formatPrice(price: number | null, currency: string): string {
    if (price === null || price <= 0) {
        return 'Free';
    }

    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
            maximumFractionDigits: 0,
        }).format(price);
    } catch {
        return `${currency} ${Math.round(price)}`;
    }
}

const RELATIVE = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

export function relativeToNow(unix: number | null): string {
    if (unix === null) {
        return '';
    }

    const diffMs = unix * 1000 - Date.now();
    const diffDays = Math.round(diffMs / 86_400_000);

    if (Math.abs(diffDays) >= 1) {
        return RELATIVE.format(diffDays, 'day');
    }

    const diffHours = Math.round(diffMs / 3_600_000);

    if (Math.abs(diffHours) >= 1) {
        return RELATIVE.format(diffHours, 'hour');
    }

    const diffMinutes = Math.round(diffMs / 60_000);

    return RELATIVE.format(diffMinutes, 'minute');
}

const TYPE_BADGE: Record<string, string> = {
    concert: 'bg-fuchsia-500/15 text-fuchsia-700 dark:text-fuchsia-300',
    conference: 'bg-blue-500/15 text-blue-700 dark:text-blue-300',
    meetup: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    workshop: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    festival: 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
    sports: 'bg-lime-500/15 text-lime-700 dark:text-lime-300',
    networking: 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300',
    exhibition: 'bg-violet-500/15 text-violet-700 dark:text-violet-300',
};

export function typeBadgeClass(type: string): string {
    return (
        TYPE_BADGE[type] ??
        'bg-neutral-500/15 text-neutral-700 dark:text-neutral-300'
    );
}
