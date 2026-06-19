<script setup lang="ts">
import { ChevronDown, Search, SlidersHorizontal, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { EventFilters, FilterOptions } from '@/types/events';

const props = defineProps<{
    options: FilterOptions;
}>();

const filters = defineModel<EventFilters>('filters', { required: true });

const emit = defineEmits<{ clear: [] }>();

const labelClass =
    'text-[11px] font-medium uppercase tracking-wide text-muted-foreground';
const fieldClass =
    'h-9 w-full rounded-lg border border-input bg-background px-3 text-sm shadow-xs outline-none transition focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 dark:bg-input/30';
const selectClass = `${fieldClass} cursor-pointer appearance-none pr-8`;

const citiesByCountry = computed(() => {
    const groups: Record<string, FilterOptions['cities']> = {};

    for (const city of props.options.cities) {
        (groups[city.country] ??= []).push(city);
    }

    return groups;
});

const hasActiveFilters = computed(
    () =>
        filters.value.q !== '' ||
        filters.value.city !== '' ||
        filters.value.to !== '' ||
        filters.value.type !== '' ||
        filters.value.status !== '',
);
</script>

<template>
    <div
        class="rounded-2xl border bg-card/80 p-3 shadow-sm ring-1 ring-black/[0.02] backdrop-blur supports-[backdrop-filter]:bg-card/60"
    >
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-[220px] flex-1 flex-col gap-1.5">
                <span :class="labelClass">
                    <SlidersHorizontal
                        class="mr-1 inline size-3 align-[-1px]"
                    />Search
                </span>
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filters.q"
                        type="search"
                        placeholder="Search events by name…"
                        class="h-9 rounded-lg pl-9"
                    />
                </div>
            </div>

            <label class="flex flex-col gap-1.5">
                <span :class="labelClass">Location</span>
                <div class="relative">
                    <select
                        v-model="filters.city"
                        :class="selectClass"
                        class="min-w-[160px]"
                    >
                        <option value="">All locations</option>
                        <optgroup
                            v-for="(cities, country) in citiesByCountry"
                            :key="country"
                            :label="country"
                        >
                            <option
                                v-for="city in cities"
                                :key="city.key"
                                :value="city.key"
                            >
                                {{ city.city }}
                            </option>
                        </optgroup>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                </div>
            </label>

            <label class="flex flex-col gap-1.5">
                <span :class="labelClass">From</span>
                <input
                    v-model="filters.from"
                    type="date"
                    :class="fieldClass"
                    class="min-w-[140px]"
                />
            </label>

            <label class="flex flex-col gap-1.5">
                <span :class="labelClass">To</span>
                <input
                    v-model="filters.to"
                    type="date"
                    :class="fieldClass"
                    class="min-w-[140px]"
                />
            </label>

            <label class="flex flex-col gap-1.5">
                <span :class="labelClass">Category</span>
                <div class="relative">
                    <select
                        v-model="filters.type"
                        :class="selectClass"
                        class="min-w-[140px] capitalize"
                    >
                        <option value="">All categories</option>
                        <option
                            v-for="type in options.types"
                            :key="type"
                            :value="type"
                            class="capitalize"
                        >
                            {{ type }}
                        </option>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                </div>
            </label>

            <label class="flex flex-col gap-1.5">
                <span :class="labelClass">Status</span>
                <div class="relative">
                    <select
                        v-model="filters.status"
                        :class="selectClass"
                        class="min-w-[130px]"
                    >
                        <option value="">Any status</option>
                        <option
                            v-for="status in options.statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status.replace('_', ' ') }}
                        </option>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                </div>
            </label>

            <label class="flex flex-col gap-1.5">
                <span :class="labelClass">Sort</span>
                <div class="relative">
                    <select
                        v-model="filters.sort"
                        :class="selectClass"
                        class="min-w-[130px]"
                    >
                        <option value="soonest">Soonest first</option>
                        <option value="latest">Latest first</option>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                </div>
            </label>

            <Button
                v-if="hasActiveFilters"
                variant="ghost"
                size="sm"
                class="text-muted-foreground hover:text-foreground"
                @click="emit('clear')"
            >
                <X class="mr-1 size-4" /> Clear
            </Button>
        </div>
    </div>
</template>
