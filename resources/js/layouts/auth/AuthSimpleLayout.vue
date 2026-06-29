<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium text-center"
                    >
                        <div class="flex size-14 items-center justify-center overflow-hidden rounded-md border bg-white p-1">
                            <template v-if="page.props.branding?.logo_light">
                                <img
                                    :src="page.props.branding.logo_light.startsWith('http') || page.props.branding.logo_light.startsWith('/') ? page.props.branding.logo_light : `/storage/${page.props.branding.logo_light}`"
                                    alt="Logo"
                                    class="size-full object-contain"
                                />
                            </template>
                            <AppLogoIcon v-else class="size-8 fill-current text-black" />
                        </div>
                        
                        <span class="text-xl font-bold tracking-tight text-foreground block mt-1">
                            {{ page.props.name }}
                        </span>
                        
                        <span v-if="page.props.branding?.tagline" class="text-xs text-muted-foreground font-normal leading-normal -mt-1 block max-w-[280px]">
                            {{ page.props.branding.tagline }}
                        </span>
                    </Link>
                    <div class="space-y-1 text-center mt-2">
                        <h1 class="text-lg font-medium">{{ title }}</h1>
                        <p class="text-xs text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
