<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, CheckCircle2, Clock3, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

const page = usePage();
const currentOrganization = computed(() => page.props.auth.currentOrganization);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-3 p-3 md:p-4">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-lg font-semibold tracking-tight">Dashboard</h1>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Operational overview for your current workspace.
                </p>
            </div>
            <Badge variant="secondary" class="w-fit gap-1.5">
                <Building2 class="size-3.5" />
                {{ currentOrganization?.name ?? 'No organization selected' }}
            </Badge>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <section class="rounded-lg border bg-card p-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium text-muted-foreground">
                        Access Context
                    </p>
                    <ShieldCheck class="size-4 text-muted-foreground" />
                </div>
                <p class="mt-2 text-xl font-semibold">
                    {{ currentOrganization ? 'Ready' : 'Pending' }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Permissions are evaluated against the active organization.
                </p>
            </section>

            <section class="rounded-lg border bg-card p-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium text-muted-foreground">
                        Account Status
                    </p>
                    <CheckCircle2 class="size-4 text-muted-foreground" />
                </div>
                <p class="mt-2 text-xl font-semibold">Active</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Your session is verified and ready for work.
                </p>
            </section>

            <section class="rounded-lg border bg-card p-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium text-muted-foreground">
                        Pending Tasks
                    </p>
                    <Clock3 class="size-4 text-muted-foreground" />
                </div>
                <p class="mt-2 text-xl font-semibold">0</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    No pending workflow items assigned.
                </p>
            </section>
        </div>

        <div class="grid flex-1 gap-3 lg:grid-cols-[1.2fr_0.8fr]">
            <section class="rounded-lg border bg-card">
                <div class="border-b px-3 py-2">
                    <h2 class="text-sm font-semibold">Recent Activity</h2>
                    <p class="text-xs text-muted-foreground">
                        System events and updates will appear here.
                    </p>
                </div>
                <div class="flex min-h-40 items-center justify-center p-4 text-center">
                    <div>
                        <p class="text-sm font-medium">No activity yet</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Activity will populate as modules are used.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border bg-card">
                <div class="border-b px-3 py-2">
                    <h2 class="text-sm font-semibold">Workspace</h2>
                    <p class="text-xs text-muted-foreground">
                        Current organization and authorization scope.
                    </p>
                </div>
                <div class="grid gap-2 p-3 text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Organization</span>
                        <span class="truncate font-medium">
                            {{ currentOrganization?.name ?? 'Not assigned' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Status</span>
                        <Badge :variant="currentOrganization ? 'default' : 'secondary'">
                            {{ currentOrganization ? 'Scoped' : 'Unscoped' }}
                        </Badge>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
