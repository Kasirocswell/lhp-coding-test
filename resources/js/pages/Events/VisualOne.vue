<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Globe2, Layers, LayoutGrid, Sparkles } from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import { onMounted, ref } from 'vue';
import EventCard from '@/components/events/EventCard.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventsTopNav from '@/components/events/EventsTopNav.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { useEventsFeed } from '@/composables/useEventsFeed';
import type { FilterOptions } from '@/types/events';

defineProps<{ filterOptions: FilterOptions }>();

const { filters, items, total, loading, loadedOnce, hasMore, loadMore, clear } =
    useEventsFeed(24);

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
    <Head title="Events · Gallery" />

    <EventsTopNav />

    <div
        class="relative isolate mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6"
    >
        <header
            class="relative isolate overflow-hidden rounded-3xl border border-border/60 bg-gradient-to-br from-card via-card to-muted/50 p-6 shadow-sm md:p-9"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-16 -z-10 size-72 rounded-full bg-fuchsia-500/15 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-24 -left-16 -z-10 size-72 rounded-full bg-violet-500/15 blur-3xl"
            />
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/60 px-3 py-1 text-xs font-medium text-muted-foreground shadow-sm backdrop-blur"
            >
                <Sparkles class="size-3.5 text-fuchsia-500" /> Curated worldwide
            </span>
            <h1
                class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-balance md:text-5xl"
            >
                Discover events worth
                <span
                    class="bg-gradient-to-r from-violet-600 via-fuchsia-600 to-rose-500 bg-clip-text text-transparent"
                    >showing up</span
                >
                for
            </h1>
            <p class="mt-3 max-w-xl text-muted-foreground">
                {{
                    total !== null
                        ? `${total.toLocaleString()} events match your filters — hand-picked happenings around the world.`
                        : 'Browse upcoming events around the world.'
                }}
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <span
                    v-if="total !== null"
                    class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/60 px-3 py-1.5 text-sm font-medium shadow-sm backdrop-blur"
                >
                    <LayoutGrid class="size-4 text-fuchsia-500" />
                    {{ total.toLocaleString() }} events
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/60 px-3 py-1.5 text-sm font-medium shadow-sm backdrop-blur"
                >
                    <Globe2 class="size-4 text-violet-500" />
                    {{ filterOptions.cities.length }} cities
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/60 px-3 py-1.5 text-sm font-medium shadow-sm backdrop-blur"
                >
                    <Layers class="size-4 text-rose-500" />
                    {{ filterOptions.types.length }} categories
                </span>
            </div>
        </header>

        <div class="sticky top-[4.5rem] z-10">
            <EventFilters
                v-model:filters="filters"
                :options="filterOptions"
                @clear="clear"
            />
        </div>

        <div
            v-if="items.length"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="(event, index) in items"
                :key="event.id"
                class="animate-in fill-mode-both fade-in slide-in-from-bottom-3"
                :style="{
                    animationDelay: `${Math.min(index % 24, 12) * 35}ms`,
                }"
            >
                <EventCard :event="event" />
            </div>
        </div>

        <div
            v-if="loading"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="n in 8"
                :key="n"
                class="overflow-hidden rounded-2xl border"
            >
                <Skeleton class="aspect-[16/10] w-full rounded-none" />
                <div class="space-y-2 p-4">
                    <Skeleton class="h-5 w-3/4" />
                    <Skeleton class="h-4 w-full" />
                    <Skeleton class="h-4 w-1/2" />
                </div>
            </div>
        </div>

        <div
            v-if="loadedOnce && !loading && items.length === 0"
            class="flex flex-col items-center gap-2 rounded-2xl border border-dashed p-14 text-center"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-muted"
            >
                <LayoutGrid class="size-5 text-muted-foreground" />
            </div>
            <p class="font-medium">No events found</p>
            <p class="max-w-sm text-sm text-muted-foreground">
                Try widening your date range, choosing a different city, or
                clearing your filters.
            </p>
        </div>

        <div ref="sentinel" class="h-px" />
        <p
            v-if="!hasMore && items.length > 0"
            class="py-4 text-center text-sm text-muted-foreground"
        >
            You've reached the end.
        </p>
    </div>
</template>
