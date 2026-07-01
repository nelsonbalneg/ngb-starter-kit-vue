<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { computed, reactive, ref, watch } from 'vue';
import {
    ChevronDown,
    ChevronRight,
    ArrowDownToLine,
    Landmark,
    ArrowLeftToLine,
    ArrowRightToLine,
    Pencil,
    Plus,
    Save,
    School,
    Trash2,
    X,
    Search,
    Building2,
} from '@lucide/vue';

import AsyncUserSelect from '@/components/site-administration/AsyncUserSelect.vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import AdminToolbar from '@/components/site-administration/AdminToolbar.vue';
import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import EmptyState from '@/components/site-administration/EmptyState.vue';
import OrgIconButton from '@/components/site-administration/OrgIconButton.vue';
import OrganizationUnitTree from '@/components/site-administration/OrganizationUnitTree.vue';
import OrgLogoDropzone from '@/components/site-administration/OrgLogoDropzone.vue';
import OrgStatusBadge from '@/components/site-administration/OrgStatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import activityLogsRoute from '@/routes/site-administration/activity-logs';
import organizationUnitsRoute from '@/routes/site-administration/organization-units';
import organizationsRoute from '@/routes/site-administration/organizations';
import type {
    ModuleActivity,
    ModuleActivityPage,
    Organization,
    OrganizationUnit,
} from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type Props = {
    organizations: Organization[];
    parentOrganizations: Pick<Organization, 'id' | 'name'>[];
    campusOrganizations: Pick<Organization, 'id' | 'name' | 'parent_id'>[];
    officeUnits: Pick<OrganizationUnit, 'id' | 'name' | 'organization_id'>[];
    filters: {
        search?: string;
        status?: string;
    };
    activities: ModuleActivityPage;
};

type OrganizationForm = {
    parent_id: number | null;
    type: 'university' | 'campus';
    name: string;
    slug: string;
    description: string;
    logo: File | null;
    logo_path: string;
    address: string;
    is_active: boolean;
};

type UnitForm = {
    organization_id: number | null;
    parent_id: number | null;
    type: OrganizationUnit['type'];
    name: string;
    logo: File | null;
    logo_path: string;
    address: string;
    description: string;
    head_user_ids: number[];
    is_active: boolean;
};

// ─── Setup ────────────────────────────────────────────────────────────────────

const props = defineProps<Props>();
const page = usePage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Organizations', href: organizationsRoute.index() },
        ],
    },
});

// ─── Permissions ──────────────────────────────────────────────────────────────

const can = (permission: string): boolean =>
    page.props.auth.permissions[permission] === true;

const canCreate = computed(() => can('access.organizations.create'));
const canUpdate = computed(() => can('access.organizations.update'));
const canDelete = computed(() => can('access.organizations.delete'));

// ─── Logo URL helper ─────────────────────────────────────────────────────────

/**
 * Converts a stored logo_path to a public-accessible URL.
 * - Paths like 'logos/abc.png' (from Storage::disk('public')) → /storage/logos/abc.png
 * - Paths already starting with / or http → used as-is
 */
const resolveLogoUrl = (path: string | null | undefined): string | null => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return `/storage/${path}`;
};

const query = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const activityPanelOpen = ref(true);
const activities = ref<ModuleActivity[]>([...props.activities.data]);
const nextActivityCursor = ref<string | null>(props.activities.next_cursor);
const activityLoading = ref(false);

const activityGroups = computed(() =>
    activities.value.reduce<Record<string, ModuleActivity[]>>(
        (groups, activity) => {
            const date = activity.created_on ?? 'Recent';
            groups[date] ??= [];
            groups[date].push(activity);

            return groups;
        },
        {},
    ),
);

const activityInitials = (activity: ModuleActivity): string => {
    const name = activity.causer?.name ?? 'System';

    return (
        name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join('') || 'S'
    );
};

const loadMoreActivities = async (): Promise<void> => {
    if (!nextActivityCursor.value || activityLoading.value) return;

    activityLoading.value = true;

    try {
        const response = await fetch(
            activityLogsRoute.index.url('organizations', {
                query: {
                    cursor: nextActivityCursor.value,
                    per_page: props.activities.per_page,
                },
            }),
        );

        if (!response.ok) {
            throw new Error('Unable to load activity logs.');
        }

        const page = (await response.json()) as ModuleActivityPage;

        activities.value.push(...page.data);
        nextActivityCursor.value = page.next_cursor;
    } catch {
        toast.error('Unable to load activity logs.');
    } finally {
        activityLoading.value = false;
    }
};

const search = (): void => {
    router.get(organizationsRoute.index().url, query, {
        preserveState: true,
        replace: true,
    });
};

const reset = (): void => {
    query.search = '';
    query.status = '';
    search();
};

const hasCampuses = (organization: Organization): boolean =>
    (organization.children ?? []).length > 0;

const hasOffices = (organization: Organization): boolean =>
    (organization.units ?? []).length > 0;

const hasDepartments = (unit: OrganizationUnit): boolean =>
    (unit.children ?? []).length > 0;

const unitIdsWithChildren = (units: OrganizationUnit[] = []): number[] =>
    units.flatMap((unit) => [
        ...(hasDepartments(unit) ? [unit.id] : []),
        ...unitIdsWithChildren(unit.children ?? []),
    ]);

type OrganizationExpansionState = {
    parents: number[];
    campuses: number[];
    offices: number[];
};

const expansionStorageKey = 'site-administration.organizations.expanded';

const loadExpansionState = (): OrganizationExpansionState => {
    if (typeof window === 'undefined') {
        return { parents: [], campuses: [], offices: [] };
    }

    const stored = window.localStorage.getItem(expansionStorageKey);

    if (!stored) {
        return { parents: [], campuses: [], offices: [] };
    }

    try {
        const parsed = JSON.parse(
            stored,
        ) as Partial<OrganizationExpansionState>;

        return {
            parents: Array.isArray(parsed.parents) ? parsed.parents : [],
            campuses: Array.isArray(parsed.campuses) ? parsed.campuses : [],
            offices: Array.isArray(parsed.offices) ? parsed.offices : [],
        };
    } catch {
        return { parents: [], campuses: [], offices: [] };
    }
};

const saveExpansionState = (): void => {
    if (typeof window === 'undefined') return;

    window.localStorage.setItem(
        expansionStorageKey,
        JSON.stringify({
            parents: [...expandedParents.value],
            campuses: [...expandedCampuses.value],
            offices: [...expandedOffices.value],
        }),
    );
};

const restoreExpandableIds = (
    savedIds: number[],
    expandableIds: number[],
): Set<number> => {
    const currentIds = new Set(expandableIds);

    return new Set(savedIds.filter((id) => currentIds.has(id)));
};

const savedExpansionState = loadExpansionState();

const expandedParents = ref<Set<number>>(
    restoreExpandableIds(
        savedExpansionState.parents,
        props.organizations
            .filter(hasCampuses)
            .map((organization) => organization.id),
    ),
);
const expandedCampuses = ref<Set<number>>(
    restoreExpandableIds(
        savedExpansionState.campuses,
        props.organizations.flatMap((parent) =>
            (parent.children ?? [])
                .filter(hasOffices)
                .map((campus) => campus.id),
        ),
    ),
);
const expandedOffices = ref<Set<number>>(
    restoreExpandableIds(
        savedExpansionState.offices,
        props.organizations.flatMap((parent) =>
            (parent.children ?? []).flatMap((campus) =>
                unitIdsWithChildren(campus.units ?? []),
            ),
        ),
    ),
);

const toggleParent = (id: number): void => {
    if (expandedParents.value.has(id)) {
        expandedParents.value.delete(id);
    } else {
        expandedParents.value.add(id);
    }

    saveExpansionState();
};

const toggleCampus = (id: number): void => {
    if (expandedCampuses.value.has(id)) {
        expandedCampuses.value.delete(id);
    } else {
        expandedCampuses.value.add(id);
    }

    saveExpansionState();
};

const toggleOffice = (id: number): void => {
    if (expandedOffices.value.has(id)) {
        expandedOffices.value.delete(id);
    } else {
        expandedOffices.value.add(id);
    }

    saveExpansionState();
};

// ─── Organization Dialog ──────────────────────────────────────────────────────

const organizationDialogOpen = ref(false);
const editingOrganization = ref<Organization | null>(null);

const organizationForm = useForm<OrganizationForm>({
    parent_id: null,
    type: 'campus',
    name: '',
    slug: '',
    description: '',
    logo: null,
    logo_path: '',
    address: '',
    is_active: true,
});

const openCreateUniversity = (): void => {
    editingOrganization.value = null;
    organizationForm.reset();
    organizationForm.clearErrors();
    organizationForm.type = 'university';
    organizationForm.parent_id = null;
    organizationForm.logo = null;
    organizationForm.is_active = true;
    orgDescriptionAuto.value = true;
    organizationDialogOpen.value = true;
};

const openCreateCampus = (parent: Organization): void => {
    editingOrganization.value = null;
    organizationForm.reset();
    organizationForm.clearErrors();
    organizationForm.type = 'campus';
    organizationForm.parent_id = parent.id;
    organizationForm.logo = null;
    organizationForm.is_active = true;
    orgDescriptionAuto.value = true;
    organizationDialogOpen.value = true;
};

const openEditOrganization = (organization: Organization): void => {
    editingOrganization.value = organization;
    organizationForm.reset();
    organizationForm.clearErrors();
    organizationForm.parent_id = organization.parent_id;
    organizationForm.type = organization.type;
    organizationForm.name = organization.name;
    organizationForm.slug = organization.slug;
    organizationForm.description = organization.description ?? '';
    organizationForm.logo = null;
    organizationForm.logo_path = organization.logo_path ?? '';
    organizationForm.address = organization.address ?? '';
    organizationForm.is_active = organization.is_active;
    orgDescriptionAuto.value = false; // existing description — don't auto-overwrite
    organizationDialogOpen.value = true;
};

const submitOrganization = (): void => {
    if (editingOrganization.value) {
        organizationForm
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(
                organizationsRoute.update(editingOrganization.value).url,
                {
                    preserveScroll: true,
                    forceFormData: true,
                    onSuccess: () => {
                        organizationDialogOpen.value = false;
                    },
                    onError: (errors) => {
                        const first = Object.values(errors)[0];
                        toast.error(first ?? 'Failed to update organization.');
                    },
                    onFinish: () => {
                        organizationForm.transform((data) => data);
                    },
                },
            );
        return;
    }

    organizationForm.post(organizationsRoute.store().url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            organizationDialogOpen.value = false;
            organizationForm.reset();
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(first ?? 'Failed to create organization.');
        },
    });
};

// ─── Unit Dialog ──────────────────────────────────────────────────────────────

const unitDialogOpen = ref(false);
const editingUnit = ref<OrganizationUnit | null>(null);

const unitForm = useForm<UnitForm>({
    organization_id: null,
    parent_id: null,
    type: 'office',
    name: '',
    logo: null,
    logo_path: '',
    address: '',
    description: '',
    head_user_ids: [],
    is_active: true,
});

const organizationUnitTypeOptions: {
    value: OrganizationUnit['type'];
    label: string;
}[] = [
    { value: 'campus', label: 'Campus' },
    { value: 'office', label: 'Office' },
    { value: 'college', label: 'College' },
    { value: 'department', label: 'Department' },
    { value: 'program', label: 'Program' },
];

const childRequiredUnitTypes: OrganizationUnit['type'][] = [
    'department',
    'program',
];

const unitTypeLabel = (type: OrganizationUnit['type']): string =>
    organizationUnitTypeOptions.find((option) => option.value === type)
        ?.label ?? 'Unit';

const isParentUnitRequired = computed(() =>
    childRequiredUnitTypes.includes(unitForm.type),
);

// ─── Auto-description ────────────────────────────────────────────────────────

const orgDescriptionAuto = ref(true);
const unitDescriptionAuto = ref(true);

watch(
    [() => organizationForm.name, () => organizationForm.address],
    ([name, address]) => {
        if (orgDescriptionAuto.value) {
            organizationForm.description = [name, address]
                .filter(Boolean)
                .join(', ');
        }
    },
);

watch([() => unitForm.name, () => unitForm.address], ([name, address]) => {
    if (unitDescriptionAuto.value) {
        unitForm.description = [name, address].filter(Boolean).join(', ');
    }
});

const openCreateOffice = (campus: Organization): void => {
    editingUnit.value = null;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.organization_id = campus.id;
    unitForm.parent_id = null;
    unitForm.type = 'office';
    unitForm.logo = null;
    unitForm.is_active = true;
    unitDescriptionAuto.value = true;
    unitDialogOpen.value = true;
};

const openCreateDepartment = (
    campus: Organization,
    office: OrganizationUnit,
): void => {
    editingUnit.value = null;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.organization_id = campus.id;
    unitForm.parent_id = office.id;
    unitForm.type = 'department';
    unitForm.logo = null;
    unitForm.is_active = true;
    unitDescriptionAuto.value = true;
    unitDialogOpen.value = true;
};

const openEditUnit = (unit: OrganizationUnit): void => {
    editingUnit.value = unit;
    unitForm.clearErrors();
    unitForm.organization_id = unit.organization_id;
    unitForm.parent_id = unit.parent_id;
    unitForm.type = unit.type;
    unitForm.name = unit.name;
    unitForm.logo = null;
    unitForm.logo_path = unit.logo_path ?? '';
    unitForm.address = unit.address ?? '';
    unitForm.description = unit.description ?? '';
    unitForm.head_user_ids = unit.heads.map((head) => head.id);
    unitForm.is_active = unit.is_active;
    unitDescriptionAuto.value = false; // existing description — don't auto-overwrite
    unitDialogOpen.value = true;
};

const submitUnit = (): void => {
    if (editingUnit.value) {
        unitForm
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(organizationUnitsRoute.update(editingUnit.value).url, {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    unitDialogOpen.value = false;
                },
                onError: (errors) => {
                    const first = Object.values(errors)[0];
                    toast.error(first ?? 'Failed to update unit.');
                },
                onFinish: () => {
                    unitForm.transform((data) => data);
                },
            });
        return;
    }

    unitForm.post(organizationUnitsRoute.store().url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            unitDialogOpen.value = false;
            unitForm.reset();
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(first ?? 'Failed to create unit.');
        },
    });
};

// ─── Computed helpers ─────────────────────────────────────────────────────────

const userSearchUrl = '/site-administration/organizations/users/search';

const orgDialogTitle = computed(() => {
    if (editingOrganization.value) return 'Edit Organization';
    return organizationForm.type === 'university'
        ? 'Add Parent Organization'
        : 'Add Campus';
});

const unitDialogTitle = computed(() => {
    if (editingUnit.value) return 'Edit Unit';
    return `Add ${unitTypeLabel(unitForm.type)}`;
});
</script>

<template>
    <Head title="Organizations" />

    <AdminPage title="" description="">
        <!-- Top Header Card -->
        <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-xs shadow-slate-900/[0.02]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-foreground">Organizations</h1>
                    <p class="mt-1 text-[13px] text-muted-foreground">Manage hierarchy, campuses, offices, departments, logos, and assigned heads.</p>
                </div>
                <Button
                    v-if="canCreate"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-xs hover:bg-primary/90"
                    @click="openCreateUniversity"
                >
                    <Plus class="h-4 w-4" /> Add Organization
                </Button>
            </div>

            <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="query.search"
                        class="h-11 w-full rounded-xl border border-border/70 bg-background pl-10 pr-3 text-sm focus-visible:ring-3 focus-visible:ring-ring focus:outline-none"
                        placeholder="Search organization name, location, or head..."
                        @keydown.enter="search"
                    />
                </div>
                <select
                    v-model="query.status"
                    class="h-11 rounded-xl border border-border/70 bg-background px-3 text-sm focus:ring-3 focus:ring-ring focus:outline-none"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <Button
                    class="h-11 rounded-xl bg-foreground text-background hover:bg-foreground/90 px-5 text-sm font-semibold"
                    @click="search"
                >
                    Search
                </Button>
                <Button
                    variant="ghost"
                    class="h-11 rounded-xl px-4 text-sm font-semibold text-muted-foreground hover:text-foreground"
                    @click="reset"
                >
                    Reset
                </Button>
            </div>
        </div>

        <div
            class="grid gap-4 mt-1"
            :class="
                activityPanelOpen
                    ? 'xl:grid-cols-[minmax(0,1fr)_320px]'
                    : 'xl:grid-cols-1'
            "
        >
            <div class="space-y-4">
                <!-- Hierarchy tree -->
                <template v-if="organizations.length">
                    <!-- ── Parent Organization Card ── -->
                    <article
                        v-for="parent in organizations"
                        :key="parent.id"
                        class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xs"
                    >
                        <!-- Parent header -->
                        <div
                            class="flex items-start justify-between gap-4 border-b border-border/30 p-4"
                        >
                            <!-- Left: chevron + icon + info -->
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <button
                                    v-if="hasCampuses(parent)"
                                    type="button"
                                    :aria-label="
                                        expandedParents.has(parent.id)
                                            ? `Collapse ${parent.name}`
                                            : `Expand ${parent.name}`
                                    "
                                    class="mt-1 flex size-6 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    @click="toggleParent(parent.id)"
                                >
                                    <ChevronDown
                                        v-if="expandedParents.has(parent.id)"
                                        class="size-4"
                                    />
                                    <ChevronRight v-else class="size-4" />
                                </button>
                                <span
                                    v-else
                                    class="size-6 shrink-0"
                                    aria-hidden="true"
                                />

                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950/50 dark:text-blue-400 dark:ring-blue-900/50"
                                >
                                    <img
                                        v-if="resolveLogoUrl(parent.logo_path)"
                                        :src="resolveLogoUrl(parent.logo_path)!"
                                        :alt="parent.name"
                                        class="size-full object-contain p-0.5"
                                        @error="
                                            (
                                                $event.target as HTMLImageElement
                                            ).style.display = 'none'
                                        "
                                    />
                                    <Building2
                                        v-else
                                        class="size-5"
                                    />
                                </span>

                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h2
                                            class="truncate text-base font-semibold text-foreground"
                                        >
                                            {{ parent.name }}
                                        </h2>
                                        <Badge
                                            variant="outline"
                                            class="text-[11px] bg-blue-50 text-blue-700 ring-1 ring-blue-100 hover:bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-900/40"
                                            >Parent</Badge
                                        >
                                        <OrgStatusBadge
                                            :active="parent.is_active"
                                        />
                                    </div>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ parent.description ?? parent.slug }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right: actions -->
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    v-if="canCreate"
                                    size="icon"
                                    variant="outline"
                                    class="size-8 rounded-lg"
                                    :title="`Add child under ${parent.name}`"
                                    :aria-label="`Add child under ${parent.name}`"
                                    @click="openCreateCampus(parent)"
                                >
                                    <Plus class="size-4" />
                                </Button>

                                <OrgIconButton
                                    v-if="canUpdate"
                                    :icon="Pencil"
                                    class="size-8 rounded-lg"
                                    label="Edit organization"
                                    @click="openEditOrganization(parent)"
                                />

                                <ConfirmAction
                                    v-if="canDelete"
                                    title="Delete Organization?"
                                    description="This action cannot be undone. The organization and related access configuration may be affected."
                                    confirm-label="Delete"
                                    @confirm="
                                        router.delete(
                                            organizationsRoute.destroy(parent)
                                                .url,
                                        )
                                    "
                                >
                                    <OrgIconButton
                                        :icon="Trash2"
                                        label="Delete organization"
                                        variant="ghost"
                                        class="size-8 rounded-lg text-destructive hover:text-destructive hover:bg-destructive/10"
                                    />
                                </ConfirmAction>
                            </div>
                        </div>

                        <!-- Campus rows (collapsible) -->
                        <div
                            v-if="
                                hasCampuses(parent) &&
                                expandedParents.has(parent.id)
                            "
                            class="space-y-3 bg-slate-50/70 p-4 pl-10 dark:bg-slate-950/20"
                        >
                            <!-- ── Campus Row ── -->
                            <section
                                v-for="campus in parent.children ?? []"
                                :key="campus.id"
                                class="rounded-2xl border border-border/70 bg-card p-4 shadow-xs"
                            >
                                <!-- Campus header -->
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div
                                        class="flex min-w-0 flex-1 items-start gap-3"
                                    >
                                        <button
                                            v-if="hasOffices(campus)"
                                            type="button"
                                            :aria-label="
                                                expandedCampuses.has(
                                                    campus.id,
                                                )
                                                    ? `Collapse ${campus.name}`
                                                    : `Expand ${campus.name}`
                                            "
                                            class="mt-1 flex size-6 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                            @click="toggleCampus(campus.id)"
                                        >
                                            <ChevronDown
                                                v-if="
                                                    expandedCampuses.has(
                                                        campus.id,
                                                    )
                                                "
                                                class="size-4"
                                            />
                                            <ChevronRight
                                                v-else
                                                class="size-4"
                                            />
                                        </button>
                                        <span
                                            v-else
                                            class="size-6 shrink-0"
                                            aria-hidden="true"
                                        />

                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-cyan-50 text-cyan-700 ring-1 ring-cyan-100 dark:bg-cyan-950/50 dark:text-cyan-400 dark:ring-cyan-900/50"
                                        >
                                            <img
                                                v-if="
                                                    resolveLogoUrl(
                                                        campus.logo_path,
                                                    )
                                                "
                                                :src="
                                                    resolveLogoUrl(
                                                        campus.logo_path,
                                                    )!
                                                "
                                                :alt="campus.name"
                                                class="size-full object-contain p-0.5"
                                                @error="
                                                    (
                                                        $event.target as HTMLImageElement
                                                    ).style.display = 'none'
                                                "
                                            />
                                            <Landmark
                                                v-else
                                                class="size-4.5"
                                            />
                                        </span>

                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <h3
                                                    class="truncate font-semibold text-foreground"
                                                >
                                                    {{ campus.name }}
                                                </h3>
                                                <Badge
                                                    variant="secondary"
                                                    class="text-[11px] bg-cyan-50 text-cyan-700 ring-1 ring-cyan-100 hover:bg-cyan-50 dark:bg-cyan-950/40 dark:text-cyan-400 dark:ring-cyan-900/40"
                                                    >Campus</Badge
                                                >
                                                <OrgStatusBadge
                                                    :active="
                                                        campus.is_active
                                                    "
                                                />
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        campus.users_count ??
                                                        0
                                                    }}
                                                    users
                                                </span>
                                            </div>
                                            <p
                                                class="mt-1 text-sm text-muted-foreground"
                                            >
                                                {{
                                                    campus.address ??
                                                    campus.description ??
                                                    campus.slug
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-1"
                                    >
                                        <Button
                                            v-if="canCreate"
                                            size="icon"
                                            variant="outline"
                                            class="size-8 rounded-lg"
                                            :title="`Add child under ${campus.name}`"
                                            :aria-label="`Add child under ${campus.name}`"
                                            @click="
                                                openCreateOffice(campus)
                                            "
                                        >
                                            <Plus class="size-4" />
                                        </Button>

                                        <OrgIconButton
                                            v-if="canUpdate"
                                            :icon="Pencil"
                                            class="size-8 rounded-lg"
                                            label="Edit campus"
                                            @click="
                                                openEditOrganization(campus)
                                            "
                                        />

                                        <ConfirmAction
                                            v-if="canDelete"
                                            title="Delete Organization?"
                                            description="This action cannot be undone. Offices and departments under this campus will be removed."
                                            confirm-label="Delete"
                                            @confirm="
                                                router.delete(
                                                    organizationsRoute.destroy(
                                                        campus,
                                                    ).url,
                                                )
                                            "
                                        >
                                            <OrgIconButton
                                                :icon="Trash2"
                                                label="Delete campus"
                                                variant="ghost"
                                                class="size-8 rounded-lg text-destructive hover:text-destructive hover:bg-destructive/10"
                                            />
                                        </ConfirmAction>
                                    </div>
                                </div>

                                <!-- Unit rows (recursive/collapsible) -->
                                <div
                                    v-if="
                                        hasOffices(campus) &&
                                        expandedCampuses.has(campus.id)
                                    "
                                    class="mt-4 space-y-3 border-l-2 border-border/70 pl-6"
                                >
                                    <OrganizationUnitTree
                                        :units="campus.units ?? []"
                                        :campus="campus"
                                        :expanded-unit-ids="
                                            expandedOffices
                                        "
                                        :can-create="canCreate"
                                        :can-update="canUpdate"
                                        :can-delete="canDelete"
                                        @toggle="toggleOffice"
                                        @create-child="
                                            openCreateDepartment
                                        "
                                        @edit="openEditUnit"
                                    />
                                </div>
                            </section>
                        </div>
                    </article>
                </template>

                <!-- Empty state -->
                <div v-else class="p-3">
                    <EmptyState
                        title="No organization hierarchy found"
                        description="Create a parent organization first, then add campuses, offices, and departments with tagged heads."
                    />
                </div>
            </div>

            <!-- Activity Panel -->
            <aside v-if="activityPanelOpen" class="rounded-2xl border border-border/70 bg-card p-4 shadow-xs h-fit sticky top-4">
                <div
                    class="flex items-center justify-between pb-3 border-b border-border/30"
                >
                    <div>
                        <h2 class="font-semibold text-foreground">Activity</h2>
                        <p class="text-xs text-muted-foreground">
                            Recent organization updates
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        class="size-8 shrink-0 rounded-lg text-muted-foreground hover:text-foreground"
                        title="Hide activity log"
                        @click="activityPanelOpen = false"
                    >
                        <ArrowRightToLine class="size-4" />
                        <span class="sr-only">Hide activity log</span>
                    </Button>
                </div>

                <div
                    v-if="activities.length"
                    class="max-h-[calc(100vh-16rem)] overflow-y-auto mt-4 space-y-4"
                >
                    <div
                        v-for="(items, date) in activityGroups"
                        :key="date"
                        class="space-y-3"
                    >
                        <p class="text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground/80 py-1">
                            {{ date }}
                        </p>

                        <div
                            v-for="activity in items"
                            :key="activity.id"
                            class="flex gap-3"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary ring-1 ring-primary/15"
                            >
                                {{ activityInitials(activity) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex min-w-0 flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground"
                                >
                                    <span class="font-semibold text-foreground">
                                        {{ activity.causer?.name ?? 'System' }}
                                    </span>
                                    <span class="text-[10px]">
                                        {{ activity.created_time }}
                                    </span>
                                </div>
                                <p class="text-[13px] leading-relaxed text-foreground mt-0.5">
                                    {{ activity.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <Button
                        v-if="nextActivityCursor"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="mt-2 w-full rounded-xl"
                        :disabled="activityLoading"
                        @click="loadMoreActivities"
                    >
                        <Spinner v-if="activityLoading" class="size-3.5" />
                        <ArrowDownToLine v-else class="size-3.5" />
                        Load older activity
                    </Button>
                </div>

                <div v-else class="p-3 mt-4">
                    <EmptyState
                        title="No activity yet"
                        description="Organization changes will appear here as the hierarchy is updated."
                    />
                </div>
            </aside>

            <Button
                v-else
                type="button"
                size="icon"
                variant="outline"
                class="fixed top-24 right-4 z-40 size-9 bg-background shadow-md"
                title="Show activity log"
                @click="activityPanelOpen = true"
            >
                <ArrowLeftToLine  class="size-4" />
                <span class="sr-only">Show activity log</span>
            </Button>
        </div>

        <!-- ─── Organization Dialog ──────────────────────────────────────────────── -->
        <Dialog v-model:open="organizationDialogOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogHeader class="px-1 pb-3">
                    <DialogTitle>{{ orgDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitOrganization">
                    <div class="max-h-[60vh] space-y-5 overflow-y-auto px-1.5 pr-2">
                        <!-- Section: Classification -->
                        <div class="space-y-3">
                            <p
                                class="ml-1 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Classification
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-[12px]"
                                        >Type
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="organizationForm.type"
                                        class="h-9 w-full rounded-md border bg-background px-3 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                        @change="
                                            organizationForm.clearErrors(
                                                'type',
                                                'parent_id',
                                            )
                                        "
                                    >
                                        <option value="university">
                                            Parent
                                        </option>
                                        <option value="campus">Campus</option>
                                    </select>
                                </div>

                                <div
                                    v-if="organizationForm.type === 'campus'"
                                    class="space-y-2"
                                >
                                    <Label class="text-[12px]"
                                        >Parent
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="organizationForm.parent_id"
                                        class="h-9 w-full rounded-md border bg-background px-3 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                        @change="
                                            organizationForm.clearErrors(
                                                'parent_id',
                                            )
                                        "
                                    >
                                        <option :value="null">
                                            — Select parent —
                                        </option>
                                        <option
                                            v-for="p in parentOrganizations"
                                            :key="p.id"
                                            :value="p.id"
                                        >
                                            {{ p.name }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="
                                            organizationForm.errors.parent_id
                                        "
                                    />
                                </div>
                            </div>
                            <InputError
                                :message="organizationForm.errors.type"
                            />
                        </div>

                        <Separator />

                        <!-- Section: Details -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Details
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-[12px]"
                                        >Name
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <Input
                                        v-model="organizationForm.name"
                                        class="h-9 px-3 text-[13px]"
                                        required
                                        @update:model-value="
                                            organizationForm.clearErrors('name')
                                        "
                                    />
                                    <InputError
                                        :message="organizationForm.errors.name"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[12px]">Slug</Label>
                                    <Input
                                        v-model="organizationForm.slug"
                                        class="h-9 px-3 font-mono text-[13px]"
                                        placeholder="auto-generated"
                                        @update:model-value="
                                            organizationForm.clearErrors('slug')
                                        "
                                    />
                                    <InputError
                                        :message="organizationForm.errors.slug"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-[12px]">Address</Label>
                                <Input
                                    v-model="organizationForm.address"
                                    class="h-9 px-3 text-[13px]"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label class="text-[12px]">Description</Label>
                                <Input
                                    v-model="organizationForm.description"
                                    class="h-9 px-3 text-[13px]"
                                    @input="orgDescriptionAuto = false"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Section: Logo -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Logo
                            </p>
                            <OrgLogoDropzone
                                v-model="organizationForm.logo_path"
                                v-model:file="organizationForm.logo"
                            />
                        </div>

                        <Separator />

                        <!-- Section: Status -->
                        <div class="flex items-center gap-2">
                            <input
                                id="org-is-active"
                                v-model="organizationForm.is_active"
                                type="checkbox"
                                class="size-4 rounded border-border accent-primary"
                            />
                            <label
                                for="org-is-active"
                                class="cursor-pointer text-[13px] font-medium"
                            >
                                Active
                            </label>
                            <span class="text-[11px] text-muted-foreground">
                                — Inactive organizations are hidden from regular
                                access flows.
                            </span>
                        </div>
                    </div>

                    <DialogFooter class="mt-4 border-t pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-9 px-4"
                            @click="organizationDialogOpen = false"
                        >
                            <X class="size-3.5" />
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            class="h-9 px-4"
                            :disabled="organizationForm.processing"
                        >
                            <Spinner
                                v-if="organizationForm.processing"
                                class="size-3.5"
                            />
                            <Save v-else-if="editingOrganization" class="size-3.5" />
                            <Plus v-else class="size-3.5" />
                            {{
                                editingOrganization ? 'Save Changes' : 'Create'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <!-- ─── Unit Dialog ──────────────────────────────────────────────────────── -->
        <Dialog v-model:open="unitDialogOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogHeader class="px-1 pb-3">
                    <DialogTitle>{{ unitDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitUnit">
                    <div class="max-h-[60vh] space-y-5 overflow-y-auto px-1.5 pr-2">
                        <!-- Section: Classification -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Classification
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-[12px]"
                                        >Campus
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="unitForm.organization_id"
                                        class="h-9 w-full rounded-md border bg-background px-3 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                        @change="
                                            unitForm.clearErrors(
                                                'organization_id',
                                                'parent_id',
                                            )
                                        "
                                    >
                                        <option :value="null">
                                            — Select campus —
                                        </option>
                                        <option
                                            v-for="campus in campusOrganizations"
                                            :key="campus.id"
                                            :value="campus.id"
                                        >
                                            {{ campus.name }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="
                                            unitForm.errors.organization_id
                                        "
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-[12px]"
                                        >Type
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="unitForm.type"
                                        class="h-9 w-full rounded-md border bg-background px-3 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                        @change="
                                            unitForm.clearErrors(
                                                'type',
                                                'parent_id',
                                            );
                                        "
                                    >
                                        <option
                                            v-for="option in organizationUnitTypeOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div
                                class="space-y-2"
                            >
                                <Label class="text-[12px]"
                                    >Parent Unit
                                    <span
                                        v-if="isParentUnitRequired"
                                        class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <select
                                    v-model="unitForm.parent_id"
                                    class="h-9 w-full rounded-md border bg-background px-3 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                    @change="
                                        unitForm.clearErrors('parent_id')
                                    "
                                >
                                    <option :value="null">
                                        {{
                                            isParentUnitRequired
                                                ? '— Select parent unit —'
                                                : '— No parent unit —'
                                        }}
                                    </option>
                                    <option
                                        v-for="office in officeUnits.filter(
                                            (item) =>
                                                item.organization_id ===
                                                unitForm.organization_id,
                                        )"
                                        :key="office.id"
                                        :value="office.id"
                                    >
                                        {{ office.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="unitForm.errors.parent_id"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Section: Details -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Details
                            </p>

                            <div class="space-y-2">
                                <Label class="text-[12px]"
                                    >Name
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    v-model="unitForm.name"
                                    class="h-9 px-3 text-[13px]"
                                    required
                                    @update:model-value="
                                        unitForm.clearErrors('name')
                                    "
                                />
                                <InputError :message="unitForm.errors.name" />
                            </div>

                            <div class="space-y-2">
                                <Label class="text-[12px]">Address</Label>
                                <Input
                                    v-model="unitForm.address"
                                    class="h-9 px-3 text-[13px]"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label class="text-[12px]">Description</Label>
                                <Input
                                    v-model="unitForm.description"
                                    class="h-9 px-3 text-[13px]"
                                    @input="unitDescriptionAuto = false"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Section: Head -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Head
                            </p>
                            <AsyncUserSelect
                                v-model="unitForm.head_user_ids"
                                :search-url="userSearchUrl"
                            />
                            <InputError
                                :message="unitForm.errors.head_user_ids"
                            />
                        </div>

                        <Separator />

                        <!-- Section: Logo -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Logo
                            </p>
                            <OrgLogoDropzone
                                v-model="unitForm.logo_path"
                                v-model:file="unitForm.logo"
                            />
                        </div>

                        <Separator />

                        <!-- Section: Status -->
                        <div class="flex items-center gap-2">
                            <input
                                id="unit-is-active"
                                v-model="unitForm.is_active"
                                type="checkbox"
                                class="size-4 rounded border-border accent-primary"
                            />
                            <label
                                for="unit-is-active"
                                class="cursor-pointer text-[13px] font-medium"
                            >
                                Active
                            </label>
                            <span class="text-[11px] text-muted-foreground">
                                — Inactive units are hidden from regular access
                                flows.
                            </span>
                        </div>
                    </div>

                    <DialogFooter class="mt-4 border-t pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-9 px-4"
                            @click="unitDialogOpen = false"
                        >
                            <X class="size-3.5" />
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            class="h-9 px-4"
                            :disabled="unitForm.processing"
                        >
                            <Spinner
                                v-if="unitForm.processing"
                                class="size-3.5"
                            />
                            <Save v-else-if="editingUnit" class="size-3.5" />
                            <Plus v-else class="size-3.5" />
                            {{ editingUnit ? 'Save Changes' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </AdminPage>
</template>
