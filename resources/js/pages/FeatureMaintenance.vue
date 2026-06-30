<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, Home, RefreshCw } from '@lucide/vue';

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
    <div class="min-h-screen bg-[#eef2f3] dark:bg-slate-900 flex items-center justify-center p-6">
        <!-- Animated background blobs -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-32 -left-32 h-96 w-96 animate-pulse rounded-full bg-amber-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-96 w-96 animate-pulse rounded-full bg-orange-400/10 blur-3xl" style="animation-delay: 1s;"></div>
        </div>

        <div class="relative w-full max-w-lg">
            <!-- Card -->
            <div class="overflow-hidden rounded-[2rem] bg-white/90 dark:bg-slate-900/90 shadow-2xl ring-1 ring-amber-200 dark:ring-amber-800/50 backdrop-blur-xl">
                <!-- Top stripe -->
                <div class="h-1.5 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500"></div>

                <div class="p-8 text-center">
                    <!-- Icon -->
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/40 dark:to-orange-900/40 ring-1 ring-amber-200 dark:ring-amber-700/50">
                        <AlertTriangle class="h-10 w-10 text-amber-500" />
                    </div>

                    <!-- Feature under maintenance label -->
                    <div class="mb-2 text-[10px] font-black uppercase tracking-[0.2em] text-amber-500 dark:text-amber-400">
                        Under Maintenance
                    </div>

                    <!-- Feature name -->
                    <h1 class="mb-4 text-2xl font-black text-slate-900 dark:text-white">
                        {{ featureName }}
                    </h1>

                    <!-- Divider -->
                    <div class="mx-auto mb-6 h-px w-16 bg-gradient-to-r from-transparent via-amber-300 to-transparent dark:via-amber-700"></div>

                    <!-- Message box -->
                    <div class="mb-8 rounded-2xl bg-amber-50 dark:bg-amber-950/40 p-5 text-left ring-1 ring-amber-200 dark:ring-amber-800/50">
                        <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-2">
                             Reason
                        </p>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                            {{ maintenanceMessage }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-center gap-3">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 dark:bg-slate-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-700 dark:hover:bg-slate-600 active:scale-95"
                        >
                            <Home class="h-4 w-4" />
                            Back to Dashboard
                        </Link>
                        <button
                            type="button"
                            @click="reloadPage"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-100 dark:bg-amber-900/40 px-5 py-2.5 text-sm font-bold text-amber-700 dark:text-amber-300 shadow-sm ring-1 ring-amber-200 dark:ring-amber-700/50 transition hover:bg-amber-200 dark:hover:bg-amber-900/60 active:scale-95"
                        >
                            <RefreshCw class="h-4 w-4" />
                            Retry
                        </button>
                    </div>
                </div>

                <!-- Footer note -->
                <div class="border-t border-amber-100 dark:border-slate-800 bg-amber-50/60 dark:bg-slate-950/40 px-8 py-4 text-center">
                    <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                        This page will be available again once maintenance is complete.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
