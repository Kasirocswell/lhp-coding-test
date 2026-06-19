<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, MapPin, Users } from '@lucide/vue';
import { computed } from 'vue';
import { formatDateTime, formatPrice, relativeToNow } from '@/lib/eventFormat';
import type { EventListItem } from '@/types/events';

const props = defineProps<{ event: EventListItem }>();

const hoverImage = computed(() => props.event.images[1] ?? props.event.image);
const when = computed(() =>
    formatDateTime(props.event.starts_at, props.event.timezone),
);
const relative = computed(() => relativeToNow(props.event.starts_at));
const place = computed(() =>
    props.event.venue
        ? `${props.event.venue} · ${props.event.location}`
        : props.event.location,
);
</script>

<template>
    <Link
        :href="`/events/${event.id}`"
        class="group relative flex flex-col overflow-hidden rounded-2xl border border-border/70 bg-card shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-fuchsia-500/30 hover:shadow-xl hover:shadow-fuchsia-500/10"
    >
        <div class="relative aspect-[16/10] overflow-hidden">
            <img
                :src="event.image"
                :alt="event.title"
                loading="lazy"
                class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.06]"
            />
            <img
                :src="hoverImage"
                :alt="event.title"
                loading="lazy"
                class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-500 group-hover:opacity-100"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/5 to-black/15"
            />

            <div
                class="absolute inset-x-0 top-0 flex items-start justify-between p-3"
            >
                <span
                    class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium text-white capitalize ring-1 ring-white/25 backdrop-blur-md"
                >
                    {{ event.type }}
                </span>
                <span
                    class="rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-neutral-900 shadow-sm"
                >
                    {{ formatPrice(event.price, event.currency) }}
                </span>
            </div>

            <div
                class="absolute inset-x-0 bottom-0 flex items-center gap-2 p-3"
            >
                <span
                    class="text-xs font-medium text-white/95 drop-shadow-sm"
                    >{{ relative }}</span
                >
                <span
                    v-if="event.status !== 'published'"
                    class="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-medium text-white capitalize backdrop-blur-sm"
                >
                    {{ event.status.replace('_', ' ') }}
                </span>
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <h3
                class="line-clamp-2 text-base leading-snug font-semibold tracking-tight transition-colors group-hover:text-fuchsia-600 dark:group-hover:text-fuchsia-400"
            >
                {{ event.title }}
            </h3>

            <div class="space-y-1.5 text-sm text-muted-foreground">
                <p class="flex items-center gap-2">
                    <CalendarDays
                        class="size-4 shrink-0 text-muted-foreground/70"
                    />
                    <span class="truncate">{{ when }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <MapPin class="size-4 shrink-0 text-muted-foreground/70" />
                    <span class="truncate">{{ place }}</span>
                </p>
            </div>

            <div
                class="mt-auto flex items-center justify-between border-t border-border/60 pt-3 text-sm"
            >
                <span class="flex items-center gap-1.5 text-muted-foreground">
                    <Users class="size-4" />
                    <template v-if="event.attendees_count > 0"
                        >{{ event.attendees_count }} going</template
                    >
                    <template v-else>Be the first</template>
                </span>
                <span
                    class="inline-flex items-center gap-1 font-medium text-fuchsia-600 dark:text-fuchsia-400"
                >
                    Details
                    <ArrowRight
                        class="size-4 transition-transform duration-300 group-hover:translate-x-1"
                    />
                </span>
            </div>
        </div>
    </Link>
</template>
