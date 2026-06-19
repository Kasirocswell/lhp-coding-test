<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock3, MapPin, Users } from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, onMounted, ref } from 'vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventsTopNav from '@/components/events/EventsTopNav.vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { useEventsFeed } from '@/composables/useEventsFeed';
import {
    dayKey,
    formatDate,
    formatPrice,
    formatTime,
    typeBadgeClass,
} from '@/lib/eventFormat';
import type { EventListItem, FilterOptions } from '@/types/events';

defineProps<{ filterOptions: FilterOptions }>();

const { filters, items, total, loading, loadedOnce, hasMore, loadMore, clear } =
    useEventsFeed(30);

interface DayGroup {
    key: string;
    label: string;
    events: EventListItem[];
}

const groups = computed<DayGroup[]>(() => {
    const out: DayGroup[] = [];
    let current: DayGroup | null = null;

    for (const event of items.value) {
        const key = dayKey(event.starts_at, event.timezone);

        if (!current || current.key !== key) {
            current = {
                key,
                label: formatDate(event.starts_at, event.timezone),
                events: [],
            };
            out.push(current);
        }

        current.events.push(event);
    }

    return out;
});

const sentinel = ref<HTMLElement | null>(null);
useIntersectionObserver(
    sentinel,
    ([entry]) => {
        if (entry?.isIntersecting) {
            loadMore();
        }
    },
    { rootMargin: '600px' },
);

onMounted(loadMore);
</script>

<template>
    <Head title="Events · Timeline" />

    <EventsTopNav />

    <div
        class="relative isolate mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6"
    >
        <header
            class="relative isolate overflow-hidden rounded-3xl border border-border/60 bg-gradient-to-br from-card via-card to-muted/50 p-6 shadow-sm md:p-9"
        >
            <div
                class="pointer-events-none absolute -top-24 -left-16 -z-10 size-72 rounded-full bg-violet-500/15 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -right-16 -bottom-24 -z-10 size-72 rounded-full bg-sky-500/15 blur-3xl"
            />
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/60 px-3 py-1 text-xs font-medium text-muted-foreground shadow-sm backdrop-blur"
            >
                <Clock3 class="size-3.5 text-violet-500" /> Day by day
            </span>
            <h1
                class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-balance md:text-5xl"
            >
                What's
                <span
                    class="bg-gradient-to-r from-violet-600 via-indigo-600 to-sky-500 bg-clip-text text-transparent"
                    >coming up</span
                >
                next
            </h1>
            <p class="mt-3 max-w-xl text-muted-foreground">
                {{
                    total !== null
                        ? `${total.toLocaleString()} events on the agenda, laid out day by day.`
                        : 'Events laid out chronologically.'
                }}
            </p>
        </header>

        <div class="sticky top-[4.5rem] z-20">
            <EventFilters
                v-model:filters="filters"
                :options="filterOptions"
                @clear="clear"
            />
        </div>

        <div v-if="groups.length" class="flex flex-col gap-8">
            <section v-for="group in groups" :key="group.key">
                <div class="sticky top-[8.5rem] z-10 mb-4 flex items-center gap-3">
                    <h2
                        class="inline-flex items-center gap-2 rounded-full border bg-card/90 px-4 py-1.5 text-sm font-semibold shadow-sm ring-1 ring-black/[0.02] backdrop-blur"
                    >
                        <span
                            class="size-2 rounded-full bg-gradient-to-br from-violet-500 to-sky-500"
                        />
                        {{ group.label }}
                    </h2>
                    <span class="text-xs font-medium text-muted-foreground"
                        >{{ group.events.length }} event{{
                            group.events.length === 1 ? '' : 's'
                        }}</span
                    >
                </div>

                <ol
                    class="relative ml-3 border-l border-border bg-gradient-to-b from-violet-500/40 via-border to-transparent bg-[length:2px_100%] bg-no-repeat pl-6"
                >
                    <li
                        v-for="event in group.events"
                        :key="event.id"
                        class="group relative mb-4 animate-in fill-mode-both fade-in slide-in-from-left-3"
                    >
                        <span
                            class="absolute top-5 -left-[31px] size-3.5 rounded-full border-2 border-background bg-gradient-to-br from-violet-500 to-sky-500 ring-2 ring-violet-500/20 transition duration-300 group-hover:scale-125 group-hover:ring-4"
                        />

                        <Link
                            :href="`/events/${event.id}`"
                            class="flex gap-4 rounded-2xl border border-border/70 bg-card p-3 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-border hover:shadow-lg hover:shadow-primary/5"
                        >
                            <div
                                class="relative hidden size-24 shrink-0 overflow-hidden rounded-lg sm:block"
                            >
                                <img
                                    :src="event.image"
                                    :alt="event.title"
                                    loading="lazy"
                                    class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <img
                                    :src="event.images[1] ?? event.image"
                                    :alt="event.title"
                                    loading="lazy"
                                    class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                                />
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-mono text-sm font-semibold text-primary"
                                        >{{
                                            formatTime(
                                                event.starts_at,
                                                event.timezone,
                                            )
                                        }}</span
                                    >
                                    <Badge
                                        variant="secondary"
                                        :class="typeBadgeClass(event.type)"
                                        class="capitalize"
                                        >{{ event.type }}</Badge
                                    >
                                    <span
                                        class="ml-auto text-sm font-semibold"
                                        >{{
                                            formatPrice(
                                                event.price,
                                                event.currency,
                                            )
                                        }}</span
                                    >
                                </div>
                                <h3
                                    class="truncate font-semibold transition-colors group-hover:text-primary"
                                >
                                    {{ event.title }}
                                </h3>
                                <p
                                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                >
                                    <MapPin class="size-3.5 shrink-0" />
                                    <span class="truncate">{{
                                        event.venue
                                            ? `${event.venue} · ${event.location}`
                                            : event.location
                                    }}</span>
                                </p>
                                <p
                                    v-if="event.attendees_count > 0"
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <Users class="size-3.5" />
                                    {{ event.attendees_count }} going
                                </p>
                            </div>
                        </Link>
                    </li>
                </ol>
            </section>
        </div>

        <div v-if="loading" class="space-y-4">
            <div
                v-for="n in 4"
                :key="n"
                class="flex gap-4 rounded-xl border p-3"
            >
                <Skeleton class="size-24 shrink-0 rounded-lg" />
                <div class="flex-1 space-y-2 py-1">
                    <Skeleton class="h-4 w-24" />
                    <Skeleton class="h-5 w-2/3" />
                    <Skeleton class="h-4 w-1/2" />
                </div>
            </div>
        </div>

        <div
            v-if="loadedOnce && !loading && items.length === 0"
            class="rounded-xl border border-dashed p-12 text-center"
        >
            <p class="font-medium">Nothing on the agenda</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Try widening your date range or clearing filters.
            </p>
        </div>

        <div ref="sentinel" class="h-px" />
        <p
            v-if="!hasMore && items.length > 0"
            class="py-4 text-center text-sm text-muted-foreground"
        >
            That's everything.
        </p>
    </div>
</template>
