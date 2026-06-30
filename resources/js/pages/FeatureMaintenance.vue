<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, Home, RefreshCw } from '@lucide/vue';
import { dashboard } from '@/routes';

const reloadPage = (): void => {
    window.location.reload();
};

defineProps({
    featureName: {
        type: String,
        default: 'This feature',
    },
    maintenanceMessage: {
        type: String,
        default: 'This feature is currently under maintenance. Please try again later.',
    },
});
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-background px-4 py-8 text-foreground"
    >
        <section
            class="w-full max-w-xl overflow-hidden rounded-lg border bg-card shadow-sm"
        >
            <div class="border-b px-5 py-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/35 dark:text-amber-300"
                    >
                        <AlertTriangle class="size-4.5" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Under Maintenance
                        </p>
                        <h1 class="mt-1 text-lg font-semibold leading-tight">
                            {{ featureName }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 px-5 py-4">
                <div
                    class="rounded-md border bg-muted/30 px-4 py-3 text-[13px]"
                >
                    <p class="mb-1 text-xs font-medium text-muted-foreground">
                        Reason
                    </p>
                    <p class="whitespace-pre-line leading-6 text-foreground">
                        {{
                            maintenanceMessage ||
                            'This feature is currently under maintenance. Please try again later.'
                        }}
                    </p>
                </div>

                <div class="text-xs leading-5 text-muted-foreground">
                    This page will be available again once maintenance is
                    complete.
                </div>
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t bg-muted/20 px-5 py-3 sm:flex-row sm:justify-end"
            >
                <button
                    type="button"
                    class="inline-flex h-8 items-center justify-center gap-2 rounded-md border bg-background px-3 text-[13px] font-medium text-foreground shadow-xs transition hover:bg-accent hover:text-accent-foreground"
                    @click="reloadPage"
                >
                    <RefreshCw class="size-3.5" />
                    Retry
                </button>
                <Link
                    :href="dashboard()"
                    class="inline-flex h-8 items-center justify-center gap-2 rounded-md bg-primary px-3 text-[13px] font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90"
                >
                    <Home class="size-3.5" />
                    Back to Dashboard
                </Link>
            </div>
        </section>
    </div>
</template>
