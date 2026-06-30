<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { LogOut, ShieldAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import impersonationRoute from '@/routes/site-administration/authentication/impersonation';

const page = usePage();

const impersonation = computed(() => page.props.impersonation);

const stopImpersonation = (): void => {
    router.post(impersonationRoute.stop().url, {}, { preserveScroll: true });
};
</script>

<template>
    <div
        v-if="impersonation?.is_impersonating"
        class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/35 dark:text-amber-100"
    >
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-3 text-[13px]">
            <div class="flex min-w-0 items-center gap-2">
                <ShieldAlert class="size-4 shrink-0" />
                <p class="truncate">
                    You are impersonating
                    <span class="font-semibold">
                        {{ impersonation.impersonated_user_name }}
                    </span>
                    <span v-if="impersonation.reference_number">
                        . Ref #: {{ impersonation.reference_number }}
                    </span>
                </p>
            </div>
            <Button
                type="button"
                size="sm"
                variant="outline"
                class="h-8 shrink-0 border-amber-300 bg-white/80 text-amber-950 hover:bg-white dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                @click="stopImpersonation"
            >
                <LogOut class="size-3.5" />
                Stop Impersonation
            </Button>
        </div>
    </div>
</template>
