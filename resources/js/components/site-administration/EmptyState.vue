<script setup lang="ts">
type Props = {
    title: string;
    description: string;
};

defineProps<Props>();
</script>

<template>
    <div class="flex min-h-32 flex-col items-center justify-center rounded-md border border-dashed p-4 text-center">
        <p class="text-sm font-medium">{{ title }}</p>
        <p class="mt-1 max-w-md text-xs text-muted-foreground">{{ description }}</p>
    </div>
</template>
