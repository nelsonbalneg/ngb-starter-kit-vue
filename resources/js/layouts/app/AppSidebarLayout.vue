<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppContent from '@/components/AppContent.vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="flex min-h-screen flex-col overflow-x-hidden"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <ImpersonationBanner />
            <div class="flex flex-1 flex-col justify-between">
                <div class="flex-1">
                    <slot />
                </div>
                <footer
                    v-if="page.props.branding?.show_footer !== false"
                    class="mt-8 border-t bg-card/30 px-6 py-4 text-center text-xs text-muted-foreground"
                >
                    {{
                        page.props.branding?.footer_text ||
                        `© ${new Date().getFullYear()} ${page.props.name || 'Enterprise Starter Kit'}. All rights reserved.`
                    }}
                </footer>
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
