<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { useGlobalAppearance } from '@/composables/useAppearance';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

useGlobalAppearance();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
