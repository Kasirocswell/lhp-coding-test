import { computed, reactive, ref, watch } from 'vue';
import type {
    EventFeedResponse,
    EventFilters,
    EventListItem,
} from '@/types/events';

function today(): string {
    return new Intl.DateTimeFormat('en-CA').format(new Date());
}

function defaultFilters(): EventFilters {
    return {
        q: '',
        city: '',
        from: today(),
        to: '',
        type: '',
        status: '',
        sort: 'soonest',
    };
}

/**
 * Shared events feed used by both visual pages. Owns the filter state, fetches
 * paginated/enriched rows from /events/data, and supports infinite scroll via
 * loadMore(). Filter changes are debounced and reset the feed.
 */
export function useEventsFeed(perPage = 24) {
    const filters = reactive<EventFilters>(defaultFilters());

    const items = ref<EventListItem[]>([]);
    const page = ref(0);
    const lastPage = ref<number | null>(null);
    const total = ref<number | null>(null);
    const loading = ref(false);
    const loadedOnce = ref(false);

    const hasMore = computed(
        () => lastPage.value === null || page.value < lastPage.value,
    );

    let requestId = 0;

    async function loadMore(): Promise<void> {
        if (loading.value || !hasMore.value) {
            return;
        }

        loading.value = true;

        const params = new URLSearchParams({
            page: String(page.value + 1),
            per_page: String(perPage),
            sort: filters.sort,
        });

        if (filters.q) {
            params.set('q', filters.q);
        }

        if (filters.city) {
            params.set('city', filters.city);
        }

        if (filters.from) {
            params.set('from', filters.from);
        }

        if (filters.to) {
            params.set('to', filters.to);
        }

        if (filters.type) {
            params.set('type', filters.type);
        }

        if (filters.status) {
            params.set('status', filters.status);
        }

        const currentRequest = ++requestId;

        try {
            const response = await fetch(`/events/data?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            const payload: EventFeedResponse = await response.json();

            // Ignore responses that were superseded by a newer filter change.
            if (currentRequest !== requestId) {
                return;
            }

            items.value.push(...payload.data);
            page.value = payload.current_page;
            lastPage.value = payload.last_page;
            total.value = payload.total;
            loadedOnce.value = true;
        } finally {
            if (currentRequest === requestId) {
                loading.value = false;
            }
        }
    }

    function reset(): void {
        items.value = [];
        page.value = 0;
        lastPage.value = null;
        total.value = null;
        loadedOnce.value = false;
    }

    function reload(): void {
        reset();
        loadMore();
    }

    function clear(): void {
        Object.assign(filters, defaultFilters());
    }

    let debounce: ReturnType<typeof setTimeout> | undefined;
    watch(
        filters,
        () => {
            clearTimeout(debounce);
            debounce = setTimeout(reload, 250);
        },
        { deep: true },
    );

    return {
        filters,
        items,
        total,
        loading,
        loadedOnce,
        hasMore,
        loadMore,
        reload,
        clear,
    };
}
