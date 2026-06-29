<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();

const page = usePage();

const appInitial = computed(() => {
    const appName = String(page.props.name ?? 'Laravel');
    const initial = appName.trim().match(/[A-Za-z0-9]/)?.[0];

    return initial?.toUpperCase() ?? 'L';
});
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        role="img"
        :aria-label="`${appInitial} logo`"
        v-bind="$attrs"
    >
        <text
            x="20"
            y="27"
            fill="currentColor"
            text-anchor="middle"
            font-family="Inter, ui-sans-serif, system-ui, sans-serif"
            font-size="24"
            font-weight="700"
        >
            {{ appInitial }}
        </text>
    </svg>
</template>
