<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { Sun, Moon } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = (): void => {
    const nextTheme = resolvedAppearance.value === 'dark' ? 'light' : 'dark';
    updateAppearance(nextTheme);
};
</script>

<template>
    <header
        class="flex h-12 shrink-0 items-center justify-between border-b border-border/70 bg-card px-4 shadow-xs dark:bg-card/70 dark:backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-10 md:px-3"
    >
        <div class="flex items-center gap-1.5">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-1">
            <Button
                variant="ghost"
                size="icon-sm"
                class="size-8 rounded-lg text-muted-foreground hover:text-foreground"
                @click="toggleTheme"
                aria-label="Toggle theme"
            >
                <Sun v-if="resolvedAppearance === 'dark'" class="size-[17px]" />
                <Moon v-else class="size-[17px]" />
            </Button>
        </div>
    </header>
</template>
