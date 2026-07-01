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
    <div class="space-y-1">
        <div
            v-for="unit in props.units"
            :key="unit.id"
            class="rounded-md bg-background ring-1 ring-border"
        >
            <div
                class="flex items-start justify-between gap-2 px-2 py-1.5"
                :style="{ marginLeft: `${Math.min(props.depth, 8) * 1.25}rem` }"
            >
                <div class="flex min-w-0 gap-2">
                    <button
                        v-if="hasChildren(unit)"
                        type="button"
                        :aria-label="
                            props.expandedUnitIds.has(unit.id)
                                ? `Collapse ${unit.name}`
                                : `Expand ${unit.name}`
                        "
                        class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="emit('toggle', unit.id)"
                    >
                        <ChevronDown
                            v-if="props.expandedUnitIds.has(unit.id)"
                            class="size-4"
                        />
                        <ChevronRight v-else class="size-4" />
                    </button>
                    <span v-else class="size-5 shrink-0" aria-hidden="true" />

                    <span
                        class="mt-0.5 flex size-6 shrink-0 items-center justify-center overflow-hidden rounded bg-muted ring-1 ring-border"
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
                            class="size-3.5 text-muted-foreground"
                        />
                        <GitBranch
                            v-else
                            class="size-3.5 text-muted-foreground"
                        />
                    </span>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <p class="truncate text-[13px] font-medium">
                                {{ unit.name }}
                            </p>
                            <Badge variant="outline" class="text-[11px]">
                                {{ labelFor(unit) }}
                            </Badge>
                            <OrgStatusBadge :active="unit.is_active" />
                        </div>
                        <p class="truncate text-[11px] text-muted-foreground">
                            {{ unit.address ?? unit.description ?? '-' }}
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <UsersRound class="size-3 text-muted-foreground" />
                            <template v-if="unit.heads.length">
                                <span
                                    v-for="head in unit.heads"
                                    :key="head.id"
                                    class="rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium"
                                >
                                    {{ head.name }}
                                </span>
                            </template>
                            <span
                                v-else
                                class="text-[11px] text-muted-foreground italic"
                            >
                                No head tagged
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1 pt-0.5">
                    <Button
                        v-if="props.canCreate"
                        size="icon"
                        variant="outline"
                        class="size-7"
                        :title="`Add child under ${unit.name}`"
                        :aria-label="`Add child under ${unit.name}`"
                        @click="emit('createChild', props.campus, unit)"
                    >
                        <Plus class="size-3.5" />
                    </Button>

                    <OrgIconButton
                        v-if="props.canUpdate"
                        :icon="Pencil"
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
                        />
                    </ConfirmAction>
                </div>
            </div>

            <div
                v-if="hasChildren(unit) && props.expandedUnitIds.has(unit.id)"
                class="border-t bg-muted/10 px-2 py-2"
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
