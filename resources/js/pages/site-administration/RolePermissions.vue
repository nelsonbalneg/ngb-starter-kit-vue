<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import EmptyState from '@/components/site-administration/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import organizationsRoute from '@/routes/site-administration/organizations';
import rolePermissionsRoute from '@/routes/site-administration/role-permissions';
import type { Permission, Role } from '@/types';

type Matrix = {
    roles: Role[];
    selectedRole: Role | null;
    permissionGroups: Record<string, Permission[]>;
    assignedPermissionIds: number[];
};

type Props = {
    matrix: Matrix;
    filters: {
        role_id?: string;
        search?: string;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Role Permissions', href: rolePermissionsRoute.index() },
        ],
    },
});

const query = reactive({
    role_id: props.matrix.selectedRole?.id ? String(props.matrix.selectedRole.id) : '',
    search: props.filters.search ?? '',
});

const collapsedGroups = ref<string[]>([]);
const form = useForm({
    permission_ids: [...props.matrix.assignedPermissionIds] as number[],
});

const groups = computed(() =>
    Object.entries(props.matrix.permissionGroups)
        .map(([name, permissions]) => ({
            name,
            permissions: permissions.filter((permission) =>
                permission.name.toLowerCase().includes(query.search.toLowerCase()),
            ),
        }))
        .filter((group) => group.permissions.length > 0),
);

const isChecked = (permissionId: number): boolean =>
    form.permission_ids.includes(permissionId);

const togglePermission = (permissionId: number): void => {
    if (isChecked(permissionId)) {
        form.permission_ids = form.permission_ids.filter((id) => id !== permissionId);
        return;
    }

    form.permission_ids = [...form.permission_ids, permissionId];
};

const toggleGroup = (permissions: Permission[]): void => {
    const ids = permissions.map((permission) => permission.id);
    const hasAll = ids.every((id) => form.permission_ids.includes(id));

    form.permission_ids = hasAll
        ? form.permission_ids.filter((id) => !ids.includes(id))
        : Array.from(new Set([...form.permission_ids, ...ids]));
};

const toggleCollapse = (group: string): void => {
    collapsedGroups.value = collapsedGroups.value.includes(group)
        ? collapsedGroups.value.filter((item) => item !== group)
        : [...collapsedGroups.value, group];
};

const changeRole = (): void => {
    router.get(rolePermissionsRoute.index().url, { role_id: query.role_id }, { preserveState: false, replace: true });
};

const save = (): void => {
    if (!props.matrix.selectedRole) {
        return;
    }

    form.put(rolePermissionsRoute.update(props.matrix.selectedRole).url);
};
</script>

<template>
    <Head title="Role Permissions" />

    <AdminPage
        title="Role Permissions"
        description="Assign grouped permissions to organization-scoped roles using the Spatie permission matrix."
    >
        <div class="rounded-lg border bg-card">
            <div class="flex flex-col gap-2 border-b p-3 md:flex-row md:items-end md:justify-between">
                <div class="grid gap-1.5 md:w-72">
                    <Label>Role</Label>
                    <select v-model="query.role_id" class="h-8 rounded-md border bg-background px-2 text-[13px]" @change="changeRole">
                        <option value="">Select role</option>
                        <option v-for="role in matrix.roles" :key="role.id" :value="String(role.id)">
                            {{ role.name }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1.5 md:w-80">
                    <Label>Search permissions</Label>
                    <Input v-model="query.search" placeholder="Search employee.view, payroll.create..." />
                </div>
                <Button :disabled="!matrix.selectedRole || form.processing" @click="save">
                    Save permissions
                </Button>
            </div>

            <div v-if="matrix.selectedRole && groups.length" class="grid gap-3 p-3">
                <div
                    v-for="group in groups"
                    :key="group.name"
                    class="rounded-md border"
                >
                    <div class="flex items-center justify-between border-b bg-muted/40 px-3 py-2">
                        <button class="text-left text-sm font-medium" type="button" @click="toggleCollapse(group.name)">
                            {{ group.name }}
                            <Badge class="ml-2" variant="secondary">{{ group.permissions.length }}</Badge>
                        </button>
                        <Button size="sm" variant="outline" @click="toggleGroup(group.permissions)">Select all</Button>
                    </div>
                    <div v-if="!collapsedGroups.includes(group.name)" class="grid gap-1 p-2 md:grid-cols-2 lg:grid-cols-4">
                        <label
                            v-for="permission in group.permissions"
                            :key="permission.id"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] hover:bg-muted"
                        >
                            <input
                                type="checkbox"
                                :checked="isChecked(permission.id)"
                                @change="togglePermission(permission.id)"
                            />
                            <span>{{ permission.name }}</span>
                        </label>
                    </div>
                </div>
            </div>
            <div v-else class="p-3">
                <EmptyState
                    title="No role selected"
                    description="Create a role and permissions, then select the role to manage its grouped permission matrix."
                />
            </div>
        </div>
    </AdminPage>
</template>

