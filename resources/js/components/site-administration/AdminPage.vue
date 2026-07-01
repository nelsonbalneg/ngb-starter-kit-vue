<script setup lang="ts">
type Props = {
    title: string;
    description: string;
};

defineProps<Props>();
</script>

<template>
    <section class="flex flex-col gap-4 p-3 md:p-5">
        <div
            class="rounded-xl border border-border/70 bg-card px-4 py-3 shadow-sm shadow-slate-900/[0.03] dark:bg-card/72 dark:shadow-slate-900/5 dark:backdrop-blur"
        >
            <h1 class="text-lg font-semibold tracking-tight text-foreground">
                {{ title }}
            </h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </section>
</template>
