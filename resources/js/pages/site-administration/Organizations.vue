<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { computed, reactive, ref, watch } from 'vue';
import {
    Building2,
    ChevronDown,
    ChevronRight,
    GitBranch,
    Landmark,
    ArrowLeftToLine,
    ArrowRightToLine,
    Pencil,
    Plus,
    School,
    Trash2,
    UsersRound,
} from '@lucide/vue';

import AsyncUserSelect from '@/components/site-administration/AsyncUserSelect.vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import AdminToolbar from '@/components/site-administration/AdminToolbar.vue';
import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import EmptyState from '@/components/site-administration/EmptyState.vue';
import OrgIconButton from '@/components/site-administration/OrgIconButton.vue';
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
    type: 'office' | 'department';
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
                (campus.units ?? [])
                    .filter(hasDepartments)
                    .map((office) => office.id),
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
    return unitForm.type === 'office' ? 'Add Office' : 'Add Department';
});
</script>

<template>
    <Head title="Organizations" />

    <AdminPage
        title="Organizations"
        description="Manage organization records, hierarchy, logos, and assigned office heads."
    >
        <div
            class="grid gap-3"
            :class="
                activityPanelOpen
                    ? 'xl:grid-cols-[minmax(0,1fr)_20rem]'
                    : 'xl:grid-cols-1'
            "
        >
            <div class="rounded-lg border bg-card">
                <!-- Toolbar -->
                <div
                    class="flex flex-col gap-2 border-b p-3 md:flex-row md:items-center md:justify-between"
                >
                    <AdminToolbar
                        v-model:search="query.search"
                        placeholder="Search organizations…"
                        @submit="search"
                        @reset="reset"
                    >
                        <select
                            v-model="query.status"
                            class="h-8 rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </AdminToolbar>

                    <Button
                        v-if="canCreate"
                        size="sm"
                        @click="openCreateUniversity"
                    >
                        <Plus class="size-3.5" />
                        Add Parent
                    </Button>
                </div>

                <!-- Hierarchy tree -->
                <div v-if="organizations.length" class="divide-y">
                    <!-- ── Parent Organization Row ── -->
                    <div v-for="parent in organizations" :key="parent.id">
                        <!-- Parent header -->
                        <div
                            class="flex items-center justify-between gap-2 bg-muted/40 px-3 py-2"
                        >
                            <!-- Left: chevron + icon + info -->
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <button
                                    v-if="hasCampuses(parent)"
                                    type="button"
                                    :aria-label="
                                        expandedParents.has(parent.id)
                                            ? `Collapse ${parent.name}`
                                            : `Expand ${parent.name}`
                                    "
                                    class="flex size-5 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
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
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                />

                                <span
                                    class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded bg-background ring-1 ring-border"
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
                                    <School
                                        v-else
                                        class="size-4 text-muted-foreground"
                                    />
                                </span>

                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
                                        <p
                                            class="truncate text-[13px] font-semibold"
                                        >
                                            {{ parent.name }}
                                        </p>
                                        <Badge
                                            variant="outline"
                                            class="text-[11px]"
                                            >Parent</Badge
                                        >
                                        <OrgStatusBadge
                                            :active="parent.is_active"
                                        />
                                    </div>
                                    <p
                                        class="truncate text-[11px] text-muted-foreground"
                                    >
                                        {{ parent.description ?? parent.slug }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right: actions -->
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    v-if="canCreate"
                                    size="sm"
                                    variant="outline"
                                    class="h-7 text-[12px]"
                                    @click="openCreateCampus(parent)"
                                >
                                    <Plus class="size-3" />
                                    Campus
                                </Button>

                                <OrgIconButton
                                    v-if="canUpdate"
                                    :icon="Pencil"
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
                                        class="text-destructive hover:text-destructive"
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
                        >
                            <div class="divide-y border-t bg-background">
                                <!-- ── Campus Row ── -->
                                <div
                                    v-for="campus in parent.children ?? []"
                                    :key="campus.id"
                                    class="pl-7"
                                >
                                    <!-- Campus header -->
                                    <div
                                        class="flex items-center justify-between gap-2 border-b px-3 py-2"
                                    >
                                        <div
                                            class="flex min-w-0 flex-1 items-center gap-2"
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
                                                class="flex size-5 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
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
                                                class="size-5 shrink-0"
                                                aria-hidden="true"
                                            />

                                            <span
                                                class="flex size-6 shrink-0 items-center justify-center overflow-hidden rounded bg-muted ring-1 ring-border"
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
                                                    class="size-3.5 text-muted-foreground"
                                                />
                                            </span>

                                            <div class="min-w-0">
                                                <div
                                                    class="flex flex-wrap items-center gap-1.5"
                                                >
                                                    <p
                                                        class="truncate text-[13px] font-medium"
                                                    >
                                                        {{ campus.name }}
                                                    </p>
                                                    <Badge
                                                        variant="secondary"
                                                        class="text-[11px]"
                                                        >Campus</Badge
                                                    >
                                                    <OrgStatusBadge
                                                        :active="
                                                            campus.is_active
                                                        "
                                                    />
                                                    <span
                                                        class="text-[11px] text-muted-foreground"
                                                    >
                                                        {{
                                                            campus.users_count ??
                                                            0
                                                        }}
                                                        users
                                                    </span>
                                                </div>
                                                <p
                                                    class="truncate text-[11px] text-muted-foreground"
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
                                                size="sm"
                                                variant="outline"
                                                class="h-7 text-[12px]"
                                                @click="
                                                    openCreateOffice(campus)
                                                "
                                            >
                                                <Plus class="size-3" />
                                                Office
                                            </Button>

                                            <OrgIconButton
                                                v-if="canUpdate"
                                                :icon="Pencil"
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
                                                />
                                            </ConfirmAction>
                                        </div>
                                    </div>

                                    <!-- Office rows (collapsible) -->
                                    <div
                                        v-if="
                                            hasOffices(campus) &&
                                            expandedCampuses.has(campus.id)
                                        "
                                    >
                                        <div
                                            class="divide-y border-b bg-muted/10 pl-7"
                                        >
                                            <!-- ── Office Row ── -->
                                            <div
                                                v-for="office in campus.units ??
                                                []"
                                                :key="office.id"
                                                class="py-2 pr-3"
                                            >
                                                <div
                                                    class="flex items-start justify-between gap-2"
                                                >
                                                    <div
                                                        class="flex min-w-0 gap-2"
                                                    >
                                                        <button
                                                            v-if="
                                                                hasDepartments(
                                                                    office,
                                                                )
                                                            "
                                                            type="button"
                                                            :aria-label="
                                                                expandedOffices.has(
                                                                    office.id,
                                                                )
                                                                    ? `Collapse ${office.name}`
                                                                    : `Expand ${office.name}`
                                                            "
                                                            class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                            @click="
                                                                toggleOffice(
                                                                    office.id,
                                                                )
                                                            "
                                                        >
                                                            <ChevronDown
                                                                v-if="
                                                                    expandedOffices.has(
                                                                        office.id,
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
                                                            class="size-5 shrink-0"
                                                            aria-hidden="true"
                                                        />

                                                        <span
                                                            class="mt-0.5 flex size-6 shrink-0 items-center justify-center overflow-hidden rounded bg-background ring-1 ring-border"
                                                        >
                                                            <img
                                                                v-if="
                                                                    resolveLogoUrl(
                                                                        office.logo_path,
                                                                    )
                                                                "
                                                                :src="
                                                                    resolveLogoUrl(
                                                                        office.logo_path,
                                                                    )!
                                                                "
                                                                :alt="
                                                                    office.name
                                                                "
                                                                class="size-full object-contain p-0.5"
                                                                @error="
                                                                    (
                                                                        $event.target as HTMLImageElement
                                                                    ).style.display =
                                                                        'none'
                                                                "
                                                            />
                                                            <Building2
                                                                v-else
                                                                class="size-3.5 text-muted-foreground"
                                                            />
                                                        </span>

                                                        <div class="min-w-0">
                                                            <div
                                                                class="flex flex-wrap items-center gap-1.5"
                                                            >
                                                                <p
                                                                    class="truncate text-[13px] font-medium"
                                                                >
                                                                    {{
                                                                        office.name
                                                                    }}
                                                                </p>
                                                                <Badge
                                                                    variant="outline"
                                                                    class="text-[11px]"
                                                                    >Office</Badge
                                                                >
                                                                <OrgStatusBadge
                                                                    :active="
                                                                        office.is_active
                                                                    "
                                                                />
                                                            </div>
                                                            <p
                                                                class="truncate text-[11px] text-muted-foreground"
                                                            >
                                                                {{
                                                                    office.address ??
                                                                    office.description ??
                                                                    '—'
                                                                }}
                                                            </p>
                                                            <div
                                                                class="mt-1 flex flex-wrap items-center gap-1"
                                                            >
                                                                <UsersRound
                                                                    class="size-3 text-muted-foreground"
                                                                />
                                                                <template
                                                                    v-if="
                                                                        office
                                                                            .heads
                                                                            .length
                                                                    "
                                                                >
                                                                    <span
                                                                        v-for="head in office.heads"
                                                                        :key="
                                                                            head.id
                                                                        "
                                                                        class="rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium"
                                                                    >
                                                                        {{
                                                                            head.name
                                                                        }}
                                                                    </span>
                                                                </template>
                                                                <span
                                                                    v-else
                                                                    class="text-[11px] text-muted-foreground italic"
                                                                >
                                                                    No head
                                                                    tagged
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="flex shrink-0 items-center gap-1 pt-0.5"
                                                    >
                                                        <Button
                                                            v-if="canCreate"
                                                            size="sm"
                                                            variant="outline"
                                                            class="h-7 text-[12px]"
                                                            @click="
                                                                openCreateDepartment(
                                                                    campus,
                                                                    office,
                                                                )
                                                            "
                                                        >
                                                            <Plus
                                                                class="size-3"
                                                            />
                                                            Dept
                                                        </Button>

                                                        <OrgIconButton
                                                            v-if="canUpdate"
                                                            :icon="Pencil"
                                                            label="Edit office"
                                                            @click="
                                                                openEditUnit(
                                                                    office,
                                                                )
                                                            "
                                                        />

                                                        <ConfirmAction
                                                            v-if="canDelete"
                                                            title="Delete Organization?"
                                                            description="This action cannot be undone. Departments under this office will also be removed."
                                                            confirm-label="Delete"
                                                            @confirm="
                                                                router.delete(
                                                                    organizationUnitsRoute.destroy(
                                                                        office,
                                                                    ).url,
                                                                )
                                                            "
                                                        >
                                                            <OrgIconButton
                                                                :icon="Trash2"
                                                                label="Delete office"
                                                                variant="ghost"
                                                            />
                                                        </ConfirmAction>
                                                    </div>
                                                </div>

                                                <!-- Department sub-rows -->
                                                <div
                                                    v-if="
                                                        hasDepartments(
                                                            office,
                                                        ) &&
                                                        expandedOffices.has(
                                                            office.id,
                                                        )
                                                    "
                                                    class="mt-2 space-y-1 border-t pt-2 pl-7"
                                                >
                                                    <div
                                                        v-for="department in office.children ??
                                                        []"
                                                        :key="department.id"
                                                        class="flex items-center justify-between gap-2 rounded-md bg-background px-2 py-1.5 ring-1 ring-border"
                                                    >
                                                        <div
                                                            class="flex min-w-0 items-center gap-2"
                                                        >
                                                            <span
                                                                class="flex size-5 shrink-0 items-center justify-center overflow-hidden rounded bg-muted ring-1 ring-border"
                                                            >
                                                                <img
                                                                    v-if="
                                                                        resolveLogoUrl(
                                                                            department.logo_path,
                                                                        )
                                                                    "
                                                                    :src="
                                                                        resolveLogoUrl(
                                                                            department.logo_path,
                                                                        )!
                                                                    "
                                                                    :alt="
                                                                        department.name
                                                                    "
                                                                    class="size-full object-contain p-0.5"
                                                                    @error="
                                                                        (
                                                                            $event.target as HTMLImageElement
                                                                        ).style.display =
                                                                            'none'
                                                                    "
                                                                />
                                                                <GitBranch
                                                                    v-else
                                                                    class="size-3 text-muted-foreground"
                                                                />
                                                            </span>
                                                            <div
                                                                class="min-w-0"
                                                            >
                                                                <p
                                                                    class="truncate text-[12px] font-medium"
                                                                >
                                                                    {{
                                                                        department.name
                                                                    }}
                                                                </p>
                                                                <p
                                                                    class="truncate text-[11px] text-muted-foreground"
                                                                >
                                                                    Head:
                                                                    <span
                                                                        v-if="
                                                                            department
                                                                                .heads
                                                                                .length
                                                                        "
                                                                    >
                                                                        {{
                                                                            department.heads
                                                                                .map(
                                                                                    (
                                                                                        h,
                                                                                    ) =>
                                                                                        h.name,
                                                                                )
                                                                                .join(
                                                                                    ', ',
                                                                                )
                                                                        }}
                                                                    </span>
                                                                    <span
                                                                        v-else
                                                                        class="italic"
                                                                        >Not
                                                                        tagged</span
                                                                    >
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="flex shrink-0 items-center gap-1"
                                                        >
                                                            <OrgIconButton
                                                                v-if="canUpdate"
                                                                :icon="Pencil"
                                                                label="Edit department"
                                                                @click="
                                                                    openEditUnit(
                                                                        department,
                                                                    )
                                                                "
                                                            />

                                                            <ConfirmAction
                                                                v-if="canDelete"
                                                                title="Delete Organization?"
                                                                description="This action cannot be undone. This department will be removed from the office hierarchy."
                                                                confirm-label="Delete"
                                                                @confirm="
                                                                    router.delete(
                                                                        organizationUnitsRoute.destroy(
                                                                            department,
                                                                        ).url,
                                                                    )
                                                                "
                                                            >
                                                                <OrgIconButton
                                                                    :icon="
                                                                        Trash2
                                                                    "
                                                                    label="Delete department"
                                                                    variant="ghost"
                                                                />
                                                            </ConfirmAction>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="p-3">
                    <EmptyState
                        title="No organization hierarchy found"
                        description="Create a parent organization first, then add campuses, offices, and departments with tagged heads."
                    />
                </div>
            </div>

            <aside v-if="activityPanelOpen" class="rounded-lg border bg-card">
                <div
                    class="flex items-start justify-between gap-2 border-b px-3 py-2"
                >
                    <div>
                        <h2 class="text-sm font-semibold">Activity</h2>
                        <p class="text-[11px] text-muted-foreground">
                            Organizations module log
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        class="size-8 shrink-0"
                        title="Hide activity log"
                        @click="activityPanelOpen = false"
                    >
                        <ArrowRightToLine class="size-4" />
                        <span class="sr-only">Hide activity log</span>
                    </Button>
                </div>

                <div
                    v-if="activities.length"
                    class="max-h-[calc(100vh-14rem)] overflow-y-auto px-3 py-3"
                >
                    <div
                        v-for="(items, date) in activityGroups"
                        :key="date"
                        class="space-y-3 pb-4 last:pb-0"
                    >
                        <div
                            class="flex items-center gap-2 text-[11px] font-medium text-muted-foreground"
                        >
                            <span class="h-px flex-1 bg-border" />
                            <span>{{ date }}</span>
                            <span class="h-px flex-1 bg-border" />
                        </div>

                        <div
                            v-for="activity in items"
                            :key="activity.id"
                            class="flex gap-2"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-xs font-semibold text-primary ring-1 ring-primary/15"
                            >
                                {{ activityInitials(activity) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex min-w-0 flex-wrap items-center gap-x-1 text-[11px]"
                                >
                                    <span class="font-semibold">
                                        {{ activity.causer?.name ?? 'System' }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        {{ activity.created_time }}
                                    </span>
                                </div>
                                <p class="text-[13px] leading-5">
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
                        class="mt-1 w-full"
                        :disabled="activityLoading"
                        @click="loadMoreActivities"
                    >
                        <Spinner v-if="activityLoading" class="size-3.5" />
                        Load older activity
                    </Button>
                </div>

                <div v-else class="p-3">
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
                <DialogHeader class="px-0 pb-2">
                    <DialogTitle>{{ orgDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitOrganization">
                    <div class="max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                        <!-- Section: Classification -->
                        <div class="space-y-3">
                            <p
                                class="ml-1 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Classification
                            </p>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="ml-1 space-y-1.5">
                                    <Label class="text-[12px]"
                                        >Type
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="organizationForm.type"
                                        class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
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
                                    class="ml-1 space-y-1.5"
                                >
                                    <Label class="text-[12px]"
                                        >Parent
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="organizationForm.parent_id"
                                        class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
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

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="text-[12px]"
                                        >Name
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <Input
                                        v-model="organizationForm.name"
                                        class="h-8 text-[13px]"
                                        required
                                        @update:model-value="
                                            organizationForm.clearErrors('name')
                                        "
                                    />
                                    <InputError
                                        :message="organizationForm.errors.name"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-[12px]">Slug</Label>
                                    <Input
                                        v-model="organizationForm.slug"
                                        class="h-8 font-mono text-[13px]"
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

                            <div class="space-y-1.5">
                                <Label class="text-[12px]">Address</Label>
                                <Input
                                    v-model="organizationForm.address"
                                    class="h-8 text-[13px]"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-[12px]">Description</Label>
                                <Input
                                    v-model="organizationForm.description"
                                    class="h-8 text-[13px]"
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
                            @click="organizationDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="organizationForm.processing"
                        >
                            <Spinner
                                v-if="organizationForm.processing"
                                class="size-3.5"
                            />
                            {{
                                editingOrganization ? 'Save Changes' : 'Create'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <!-- ─── Unit (Office / Department) Dialog ────────────────────────────────── -->
        <Dialog v-model:open="unitDialogOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogHeader class="px-0 pb-2">
                    <DialogTitle>{{ unitDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitUnit">
                    <div class="max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                        <!-- Section: Classification -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Classification
                            </p>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="text-[12px]"
                                        >Campus
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="unitForm.organization_id"
                                        class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
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

                                <div class="space-y-1.5">
                                    <Label class="text-[12px]"
                                        >Type
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <select
                                        v-model="unitForm.type"
                                        class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                    >
                                        <option value="office">Office</option>
                                        <option value="department">
                                            Department
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div
                                v-if="unitForm.type === 'department'"
                                class="space-y-1.5"
                            >
                                <Label class="text-[12px]"
                                    >Parent Office
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <select
                                    v-model="unitForm.parent_id"
                                    class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                >
                                    <option :value="null">
                                        — Select office —
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

                            <div class="space-y-1.5">
                                <Label class="text-[12px]"
                                    >Name
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    v-model="unitForm.name"
                                    class="h-8 text-[13px]"
                                    required
                                    @update:model-value="
                                        unitForm.clearErrors('name')
                                    "
                                />
                                <InputError :message="unitForm.errors.name" />
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-[12px]">Address</Label>
                                <Input
                                    v-model="unitForm.address"
                                    class="h-8 text-[13px]"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-[12px]">Description</Label>
                                <Input
                                    v-model="unitForm.description"
                                    class="h-8 text-[13px]"
                                    @input="unitDescriptionAuto = false"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Section: Head of Office -->
                        <div class="space-y-3">
                            <p
                                class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Head of Office
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
                            @click="unitDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="unitForm.processing"
                        >
                            <Spinner
                                v-if="unitForm.processing"
                                class="size-3.5"
                            />
                            {{ editingUnit ? 'Save Changes' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </AdminPage>
</template>
