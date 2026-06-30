<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useGlobalAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

useGlobalAppearance();

const page = usePage();
const layout = computed(() =>
    page.props.appearance?.navigation === 'top'
        ? AppHeaderLayout
        : AppSidebarLayout,
);
</script>

<template>
    <component :is="layout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
