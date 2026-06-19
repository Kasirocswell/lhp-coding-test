<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useIntersectionObserver } from '@vueuse/core';
import { onMounted, ref } from 'vue';
import EventFilters from '@/components/events/EventFilters.vue';
import { Badge } from '@/components/ui/badge';
import { useEventsFeed } from '@/composables/useEventsFeed';
import { formatDateTime, formatPrice } from '@/lib/eventFormat';
import type { FilterOptions } from '@/types/events';

defineProps<{ filterOptions: FilterOptions }>();

const { filters, items, total, loading, hasMore, loadMore, clear } =
    useEventsFeed(50);

const statusVariant = (status: string) => {
    switch (status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
};

const sentinel = ref<HTMLElement | null>(null);
useIntersectionObserver(
    sentinel,
    ([entry]) => {
        if (entry?.isIntersecting) {
            loadMore();
        }
    },
    { rootMargin: '400px' },
);

onMounted(loadMore);
</script>

<template>
    <Head title="Events" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Events</h1>
            <p class="text-sm text-muted-foreground">
                {{
                    total !== null
                        ? `${total.toLocaleString()} total events`
                        : '—'
                }}
            </p>
        </div>

        <EventFilters
            v-model:filters="filters"
            :options="filterOptions"
            @clear="clear"
        />

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-3 py-2 font-medium">Event</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Location</th>
                        <th class="px-3 py-2 font-medium">When</th>
                        <th class="px-3 py-2 font-medium">Price</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="event in items"
                        :key="event.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-3 py-2 font-medium">{{ event.title }}</td>
                        <td class="px-3 py-2">
                            <Badge :variant="statusVariant(event.status)">{{
                                event.status
                            }}</Badge>
                        </td>
                        <td class="px-3 py-2">{{ event.location }}</td>
                        <td class="px-3 py-2">
                            {{
                                formatDateTime(event.starts_at, event.timezone)
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{ formatPrice(event.price, event.currency) }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <Link
                                :href="`/events/${event.id}`"
                                class="text-primary hover:underline"
                                >View</Link
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div ref="sentinel" class="h-px"></div>
        <div class="py-2 text-sm text-muted-foreground">
            <span v-if="loading">loading…</span>
            <span v-else-if="!hasMore && items.length">End of results.</span>
        </div>
    </div>
</template>
