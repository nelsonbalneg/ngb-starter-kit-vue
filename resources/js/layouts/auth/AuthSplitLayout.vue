<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <div class="absolute inset-0 bg-zinc-900" />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium gap-3"
            >
                <div class="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-md bg-white p-1">
                    <img
                        v-if="page.props.branding?.logo_light"
                        :src="page.props.branding.logo_light.startsWith('http') || page.props.branding.logo_light.startsWith('/') ? page.props.branding.logo_light : `/storage/${page.props.branding.logo_light}`"
                        alt="Logo"
                        class="size-full object-contain"
                    />
                    <AppLogoIcon v-else class="size-6 fill-current text-black" />
                </div>
                <div class="flex flex-col text-left">
                    <span class="leading-none font-semibold">{{ name }}</span>
                    <span v-if="page.props.branding?.tagline" class="text-xs text-white/70 font-normal leading-none mt-1">
                        {{ page.props.branding.tagline }}
                    </span>
                </div>
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
