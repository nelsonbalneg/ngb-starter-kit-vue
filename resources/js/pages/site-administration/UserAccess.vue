<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import organizationsRoute from '@/routes/site-administration/organizations';
import userAccessRoute from '@/routes/site-administration/user-access';
import usersRoute from '@/routes/site-administration/users';
import type { AdminUser, Organization, Permission, Role } from '@/types';

type AccessPayload = {
    user: AdminUser;
    roles: Pick<Role, 'id' | 'name'>[];
    permissions: Pick<Permission, 'id' | 'name' | 'group'>[];
    assignedRoleIds: number[];
    assignedPermissionIds: number[];
    organizations: Pick<Organization, 'id' | 'name' | 'slug'>[];
};

type Props = {
    access: AccessPayload;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Users', href: usersRoute.index() },
        ],
    },
});

const form = useForm({
    organization_ids: props.access.user.organizations.map((organization) => organization.id),
    role_ids: [...props.access.assignedRoleIds],
    permission_ids: [...props.access.assignedPermissionIds],
});

const groupedPermissions = props.access.permissions.reduce<Record<string, Pick<Permission, 'id' | 'name' | 'group'>[]>>(
    (groups, permission) => {
        groups[permission.group] = [...(groups[permission.group] ?? []), permission];
        return groups;
    },
    {},
);

const save = (): void => {
    form.put(userAccessRoute.update(props.access.user).url);
};
</script>

<template>
    <Head :title="`User Access - ${access.user.name}`" />

    <AdminPage
        title="User Access"
        :description="`Manage organization membership, roles, and direct permissions for ${access.user.name}.`"
    >
        <div class="grid gap-3 lg:grid-cols-[1fr_2fr]">
            <div class="rounded-lg border bg-card p-3">
                <h2 class="text-sm font-semibold">{{ access.user.name }}</h2>
                <p class="text-xs text-muted-foreground">{{ access.user.email }}</p>
                <div class="mt-3 flex flex-wrap gap-1">
                    <Badge
                        v-for="organization in access.user.organizations"
                        :key="organization.id"
                        variant="secondary"
                    >
                        {{ organization.name }}
                    </Badge>
                </div>
            </div>

            <form class="grid gap-3" @submit.prevent="save">
                <div class="rounded-lg border bg-card">
                    <div class="border-b px-3 py-2">
                        <h2 class="text-sm font-semibold">Organizations</h2>
                        <p class="text-xs text-muted-foreground">Controls team membership for access scoping.</p>
                    </div>
                    <div class="grid gap-1 p-3 md:grid-cols-2">
                        <label
                            v-for="organization in access.organizations"
                            :key="organization.id"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] hover:bg-muted"
                        >
                            <input v-model="form.organization_ids" type="checkbox" :value="organization.id" />
                            {{ organization.name }}
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border bg-card">
                    <div class="border-b px-3 py-2">
                        <h2 class="text-sm font-semibold">Roles</h2>
                        <p class="text-xs text-muted-foreground">Roles are scoped to the active organization.</p>
                    </div>
                    <div class="grid gap-1 p-3 md:grid-cols-2">
                        <label
                            v-for="role in access.roles"
                            :key="role.id"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] hover:bg-muted"
                        >
                            <input v-model="form.role_ids" type="checkbox" :value="role.id" />
                            {{ role.name }}
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border bg-card">
                    <div class="border-b px-3 py-2">
                        <h2 class="text-sm font-semibold">Direct Permissions</h2>
                        <p class="text-xs text-muted-foreground">Use sparingly for exceptions that cannot be represented by a role.</p>
                    </div>
                    <div class="grid gap-3 p-3">
                        <div v-for="(permissions, group) in groupedPermissions" :key="group">
                            <h3 class="mb-1 text-xs font-semibold text-muted-foreground">{{ group }}</h3>
                            <div class="grid gap-1 md:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="permission in permissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] hover:bg-muted"
                                >
                                    <input v-model="form.permission_ids" type="checkbox" :value="permission.id" />
                                    {{ permission.name }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost"><a :href="usersRoute.index().url">Cancel</a></Button>
                    <Button type="submit" :disabled="form.processing">Save access</Button>
                </div>
            </form>
        </div>
    </AdminPage>
</template>

