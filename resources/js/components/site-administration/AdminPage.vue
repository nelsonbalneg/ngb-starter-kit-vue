<script setup lang="ts">
type Props = {
    title: string;
    description: string;
};

defineProps<Props>();
</script>

<template>
    <section class="flex flex-col gap-3 p-3 md:p-4">
        <div>
            <h1 class="text-lg font-semibold tracking-tight">{{ title }}</h1>
            <p class="mt-0.5 text-xs text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </section>
</template>
