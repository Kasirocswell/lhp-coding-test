<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    Clock,
    MapPin,
    Ticket,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EventsTopNav from '@/components/events/EventsTopNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatDate,
    formatDateTime,
    formatPrice,
    formatTime,
    relativeToNow,
    typeBadgeClass,
} from '@/lib/eventFormat';
import type { EventDetail } from '@/types/events';

const props = defineProps<{
    event: EventDetail;
    attendees: Array<{ name: string; status: string }>;
}>();

const activeImage = ref(props.event.images[0]);

const viewerTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
const showViewerTime = computed(() => viewerTimezone !== props.event.timezone);

const eventDate = computed(() =>
    formatDate(props.event.starts_at, props.event.timezone),
);
const venueTime = computed(() =>
    formatTime(props.event.starts_at, props.event.timezone),
);
const viewerTime = computed(() =>
    formatDateTime(props.event.starts_at, viewerTimezone),
);

const form = useForm({
    name: '',
    email: '',
    status: 'interested' as 'interested' | 'attending',
});

function submit() {
    form.post(`/events/${props.event.id}/attendees`, {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email'),
    });
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}
</script>

<template>
    <Head :title="event.title" />

    <EventsTopNav />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
        <Link
            href="/events-visual-1"
            class="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to events
        </Link>

        <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            <div class="flex flex-col gap-4">
                <div class="overflow-hidden rounded-2xl border bg-muted">
                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-300"
                        enter-from-class="opacity-0 scale-[1.02]"
                        leave-active-class="transition duration-200"
                        leave-to-class="opacity-0"
                    >
                        <img
                            :key="activeImage"
                            :src="activeImage"
                            :alt="event.title"
                            class="aspect-[16/9] w-full object-cover"
                        />
                    </Transition>
                </div>
                <div class="flex gap-3">
                    <button
                        v-for="(image, index) in event.images"
                        :key="index"
                        type="button"
                        class="overflow-hidden rounded-lg border-2 transition"
                        :class="
                            activeImage === image
                                ? 'border-primary'
                                : 'border-transparent opacity-70 hover:opacity-100'
                        "
                        @click="activeImage = image"
                    >
                        <img
                            :src="image"
                            :alt="`${event.title} ${index + 1}`"
                            class="h-16 w-24 object-cover"
                        />
                    </button>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="secondary"
                            :class="typeBadgeClass(event.type)"
                            class="capitalize"
                            >{{ event.type }}</Badge
                        >
                        <Badge variant="outline" class="capitalize">{{
                            event.status.replace('_', ' ')
                        }}</Badge>
                        <span class="text-sm text-muted-foreground">{{
                            relativeToNow(event.starts_at)
                        }}</span>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ event.title }}
                    </h1>
                    <p
                        v-if="event.description"
                        class="leading-relaxed text-muted-foreground"
                    >
                        {{ event.description }}
                    </p>

                    <div
                        v-if="event.tags?.length"
                        class="flex flex-wrap gap-2 pt-1"
                    >
                        <span
                            v-for="tag in event.tags"
                            :key="tag"
                            class="rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground"
                            >#{{ tag }}</span
                        >
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-4">
                <div
                    class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-start gap-3">
                        <CalendarDays
                            class="mt-0.5 size-5 shrink-0 text-primary"
                        />
                        <div>
                            <p class="font-medium">{{ eventDate }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ venueTime }} ({{ event.timezone }})
                            </p>
                        </div>
                    </div>
                    <div v-if="showViewerTime" class="flex items-start gap-3">
                        <Clock class="mt-0.5 size-5 shrink-0 text-primary" />
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Your local time
                            </p>
                            <p class="font-medium">{{ viewerTime }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <MapPin class="mt-0.5 size-5 shrink-0 text-primary" />
                        <div>
                            <p class="font-medium">
                                {{ event.venue ?? event.location }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ event.location }}
                            </p>
                            <p
                                v-if="event.latitude !== null"
                                class="text-xs text-muted-foreground/70"
                            >
                                {{ event.latitude.toFixed(3) }},
                                {{ event.longitude?.toFixed(3) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <Ticket class="mt-0.5 size-5 shrink-0 text-primary" />
                        <p class="font-medium">
                            {{ formatPrice(event.price, event.currency)
                            }}<span
                                v-if="event.price && event.price > 0"
                                class="text-sm font-normal text-muted-foreground"
                            >
                                from</span
                            >
                        </p>
                    </div>
                    <div v-if="event.organizer" class="flex items-start gap-3">
                        <Building2
                            class="mt-0.5 size-5 shrink-0 text-primary"
                        />
                        <div>
                            <p class="font-medium">{{ event.organizer }}</p>
                            <p
                                v-if="event.capacity"
                                class="text-sm text-muted-foreground"
                            >
                                Capacity {{ event.capacity.toLocaleString() }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <Users class="size-5 text-primary" />
                        <h2 class="font-semibold">Register your interest</h2>
                    </div>

                    <div
                        v-if="attendees.length"
                        class="flex items-center gap-2"
                    >
                        <div class="flex -space-x-2">
                            <span
                                v-for="(attendee, index) in attendees.slice(
                                    0,
                                    5,
                                )"
                                :key="index"
                                class="flex size-8 items-center justify-center rounded-full border-2 border-background bg-primary/10 text-xs font-semibold text-primary"
                            >
                                {{ initials(attendee.name) }}
                            </span>
                        </div>
                        <span class="text-sm text-muted-foreground"
                            >{{ event.attendees_count.toLocaleString() }}
                            {{
                                event.attendees_count === 1
                                    ? 'person is'
                                    : 'people are'
                            }}
                            going</span
                        >
                    </div>

                    <form class="flex flex-col gap-3" @submit.prevent="submit">
                        <div class="flex flex-col gap-1.5">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="Your name"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="you@example.com"
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <label
                                v-for="option in [
                                    'interested',
                                    'attending',
                                ] as const"
                                :key="option"
                                class="flex flex-1 cursor-pointer items-center justify-center rounded-md border px-3 py-2 text-sm capitalize transition"
                                :class="
                                    form.status === option
                                        ? 'border-primary bg-primary/10 font-medium text-primary'
                                        : 'hover:bg-accent'
                                "
                            >
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    :value="option"
                                    class="sr-only"
                                />
                                {{ option }}
                            </label>
                        </div>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing ? 'Adding you…' : 'Count me in'
                            }}
                        </Button>
                        <p class="text-center text-xs text-muted-foreground">
                            We'll email you a confirmation and reminders before
                            the event.
                        </p>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</template>
