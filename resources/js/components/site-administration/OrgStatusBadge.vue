<script setup lang="ts">
type Props = {
    active: boolean;
};

defineProps<Props>();
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset',
            active
                ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30'
                : 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-gray-800/40 dark:text-gray-400 dark:ring-gray-500/30',
        ]"
    >
        {{ active ? 'Active' : 'Inactive' }}
    </span>
</template>
