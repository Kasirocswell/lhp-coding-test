<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarRange, LayoutGrid, Sparkles } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const path = computed(() => {
    try {
        return new URL(page.url, 'http://localhost').pathname;
    } catch {
        return page.url;
    }
});

const tabs = [
    { label: 'Gallery', href: '/events-visual-1', icon: LayoutGrid },
    { label: 'Timeline', href: '/events-visual-2', icon: CalendarRange },
];
</script>

<template>
    <div
        class="sticky top-0 z-30 border-b border-border/60 bg-background/70 backdrop-blur-xl supports-[backdrop-filter]:bg-background/55"
    >
        <div
            class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 md:px-6"
        >
            <Link
                href="/events-visual-1"
                class="group flex items-center gap-2.5"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-rose-500 text-white shadow-lg shadow-fuchsia-500/25 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3"
                >
                    <Sparkles class="size-5" />
                </span>
                <span class="flex flex-col leading-none">
                    <span class="text-base font-bold tracking-tight"
                        >Evently</span
                    >
                    <span
                        class="text-[11px] font-medium tracking-wide text-muted-foreground"
                        >Discover what's on</span
                    >
                </span>
            </Link>

            <nav
                class="flex items-center gap-1 rounded-full border border-border/70 bg-card/60 p-1 shadow-sm"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.href"
                    :href="tab.href"
                    class="relative inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors duration-200"
                    :class="
                        path === tab.href
                            ? 'bg-foreground text-background shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    <component :is="tab.icon" class="size-4" />
                    <span class="hidden sm:inline">{{ tab.label }}</span>
                </Link>
            </nav>
        </div>
    </div>
</template>
