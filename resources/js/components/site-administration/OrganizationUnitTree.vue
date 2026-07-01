<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Building2,
    ChevronDown,
    ChevronRight,
    GitBranch,
    Pencil,
    Plus,
    Trash2,
    UsersRound,
} from '@lucide/vue';

import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import OrgIconButton from '@/components/site-administration/OrgIconButton.vue';
import OrgStatusBadge from '@/components/site-administration/OrgStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import organizationUnitsRoute from '@/routes/site-administration/organization-units';
import type { Organization, OrganizationUnit } from '@/types';

defineOptions({
    name: 'OrganizationUnitTree',
});

type Props = {
    units: OrganizationUnit[];
    campus: Organization;
    expandedUnitIds: Set<number>;
    canCreate: boolean;
    canUpdate: boolean;
    canDelete: boolean;
    depth?: number;
};

const props = withDefaults(defineProps<Props>(), {
    depth: 0,
});

const emit = defineEmits<{
    toggle: [id: number];
    createChild: [campus: Organization, unit: OrganizationUnit];
    edit: [unit: OrganizationUnit];
}>();

const hasChildren = (unit: OrganizationUnit): boolean =>
    (unit.children ?? []).length > 0;

const resolveLogoUrl = (path?: string | null): string | null => {
    if (!path) {
        return null;
    }

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    if (path.startsWith('/')) {
        return path;
    }

    return `/storage/${path}`;
};

const unitTypeLabels: Record<OrganizationUnit['type'], string> = {
    campus: 'Campus',
    office: 'Office',
    college: 'College',
    department: 'Department',
    program: 'Program',
};

const labelFor = (unit: OrganizationUnit): string => unitTypeLabels[unit.type];
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="unit in props.units"
            :key="unit.id"
            class="rounded-xl border border-border/70 bg-muted/30 dark:bg-muted/15 p-4 shadow-xs"
        >
            <div
                class="flex items-start justify-between gap-4"
                :style="{ marginLeft: `${Math.min(props.depth, 8) * 1.25}rem` }"
            >
                <div class="flex min-w-0 gap-3">
                    <button
                        v-if="hasChildren(unit)"
                        type="button"
                        :aria-label="
                            props.expandedUnitIds.has(unit.id)
                                ? `Collapse ${unit.name}`
                                : `Expand ${unit.name}`
                        "
                        class="mt-1 flex size-6 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="emit('toggle', unit.id)"
                    >
                        <ChevronDown
                            v-if="props.expandedUnitIds.has(unit.id)"
                            class="size-4"
                        />
                        <ChevronRight v-else class="size-4" />
                    </button>
                    <span v-else class="size-6 shrink-0" aria-hidden="true" />

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-card text-muted-foreground ring-1 ring-border"
                    >
                        <img
                            v-if="resolveLogoUrl(unit.logo_path)"
                            :src="resolveLogoUrl(unit.logo_path)!"
                            :alt="unit.name"
                            class="size-full object-contain p-0.5"
                            @error="
                                ($event.target as HTMLImageElement).style.display =
                                    'none'
                            "
                        />
                        <Building2
                            v-else-if="unit.type === 'office'"
                            class="size-4"
                        />
                        <GitBranch
                            v-else
                            class="size-4"
                        />
                    </span>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-semibold text-foreground">
                                {{ unit.name }}
                            </h4>
                            <Badge
                                variant="outline"
                                class="text-[11px] bg-card border-border/70"
                            >
                                {{ labelFor(unit) }}
                            </Badge>
                            <OrgStatusBadge :active="unit.is_active" />
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ unit.address ?? unit.description ?? '-' }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <template v-if="unit.heads.length">
                                <span
                                    v-for="head in unit.heads"
                                    :key="head.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-200/70 dark:bg-slate-800/80 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    <UsersRound class="h-3.5 w-3.5" /> {{ head.name }}
                                </span>
                            </template>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-xs italic text-muted-foreground/80"
                            >
                                <UsersRound class="h-3.5 w-3.5" /> No head tagged
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1 pt-0.5">
                    <Button
                        v-if="props.canCreate"
                        size="icon"
                        variant="outline"
                        class="size-8 rounded-lg"
                        :title="`Add child under ${unit.name}`"
                        :aria-label="`Add child under ${unit.name}`"
                        @click="emit('createChild', props.campus, unit)"
                    >
                        <Plus class="size-4" />
                    </Button>

                    <OrgIconButton
                        v-if="props.canUpdate"
                        :icon="Pencil"
                        class="size-8 rounded-lg"
                        :label="`Edit ${labelFor(unit).toLowerCase()}`"
                        @click="emit('edit', unit)"
                    />

                    <ConfirmAction
                        v-if="props.canDelete"
                        title="Delete Organization Unit?"
                        description="This action cannot be undone. Child units under this item will also be removed."
                        confirm-label="Delete"
                        @confirm="
                            router.delete(
                                organizationUnitsRoute.destroy(unit).url,
                            )
                        "
                    >
                        <OrgIconButton
                            :icon="Trash2"
                            :label="`Delete ${labelFor(unit).toLowerCase()}`"
                            variant="ghost"
                            class="size-8 rounded-lg text-destructive hover:text-destructive hover:bg-destructive/10"
                        />
                    </ConfirmAction>
                </div>
            </div>

            <div
                v-if="hasChildren(unit) && props.expandedUnitIds.has(unit.id)"
                class="mt-3 space-y-3 border-l-2 border-border/70 pl-6"
            >
                <OrganizationUnitTree
                    :units="unit.children ?? []"
                    :campus="props.campus"
                    :expanded-unit-ids="props.expandedUnitIds"
                    :can-create="props.canCreate"
                    :can-update="props.canUpdate"
                    :can-delete="props.canDelete"
                    :depth="props.depth + 1"
                    @toggle="emit('toggle', $event)"
                    @create-child="
                        (campus, childUnit) =>
                            emit('createChild', campus, childUnit)
                    "
                    @edit="emit('edit', $event)"
                />
            </div>
        </div>
    </div>
</template>
