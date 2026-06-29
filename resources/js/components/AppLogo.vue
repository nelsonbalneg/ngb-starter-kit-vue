<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
</script>

<template>
    <div
        class="flex aspect-square size-7 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <template v-if="page.props.branding?.logo_light">
            <img
                :src="page.props.branding.logo_light.startsWith('http') || page.props.branding.logo_light.startsWith('/') ? page.props.branding.logo_light : `/storage/${page.props.branding.logo_light}`"
                alt="Logo"
                class="size-full object-contain dark:hidden p-0.5"
            />
            <img
                v-if="page.props.branding?.logo_dark"
                :src="page.props.branding.logo_dark.startsWith('http') || page.props.branding.logo_dark.startsWith('/') ? page.props.branding.logo_dark : `/storage/${page.props.branding.logo_dark}`"
                alt="Logo"
                class="size-full object-contain hidden dark:block p-0.5"
            />
            <img
                v-else
                :src="page.props.branding.logo_light.startsWith('http') || page.props.branding.logo_light.startsWith('/') ? page.props.branding.logo_light : `/storage/${page.props.branding.logo_light}`"
                alt="Logo"
                class="size-full object-contain hidden dark:block p-0.5 invert"
            />
        </template>
        <AppLogoIcon v-else class="size-4 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-0.5 grid flex-1 text-left text-[13px]">
        <span class="truncate leading-tight font-semibold">
            {{ page.props.name }}
        </span>
        <span v-if="page.props.branding?.tagline" class="truncate text-[10px] text-muted-foreground font-normal leading-none mt-0.5">
            {{ page.props.branding.tagline }}
        </span>
    </div>
</template>
