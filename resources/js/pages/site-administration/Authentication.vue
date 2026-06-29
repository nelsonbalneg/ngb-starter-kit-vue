<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import {
    Ellipsis,
    KeyRound,
    Lock,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    Unlock,
    WandSparkles,
} from '@lucide/vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import AdminPagination from '@/components/site-administration/AdminPagination.vue';
import AdminToolbar from '@/components/site-administration/AdminToolbar.vue';
import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import EmptyState from '@/components/site-administration/EmptyState.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import authenticationRoute from '@/routes/site-administration/authentication';
import organizationsRoute from '@/routes/site-administration/organizations';
import rolePermissionsRoute from '@/routes/site-administration/role-permissions';
import rolesRoute from '@/routes/site-administration/roles';
import userAccessRoute from '@/routes/site-administration/user-access';
import usersRoute from '@/routes/site-administration/users';
import type {
    AdminUser,
    Organization,
    Paginated,
    Permission,
    Role,
} from '@/types';

type AuthenticationTab = {
    key: 'users' | 'roles' | 'role-permissions' | 'permissions';
    label: string;
    permission: string;
};

type Matrix = {
    roles: Role[];
    selectedRole: Role | null;
    permissionGroups: Record<string, Permission[]>;
    assignedPermissionIds: number[];
};

type Payload = {
    users?: Paginated<AdminUser>;
    organizations?: Pick<Organization, 'id' | 'name'>[];
    roles?: Paginated<Role> | Role[];
    permissions?: Paginated<Permission>;
    groups?: string[];
    matrix?: Matrix;
};

type Props = {
    activeTab: AuthenticationTab['key'];
    tabs: AuthenticationTab[];
    filters: {
        search?: string;
        organization_id?: string;
        status?: string;
        group?: string;
        role_id?: string;
    };
    payload: Payload;
};

type UserForm = {
    name: string;
    email: string;
    password: string;
    organization_ids: number[];
    is_active: boolean;
    photo: File | null;
    _method?: 'put';
};

type RoleForm = {
    name: string;
    description: string;
    stay_on_authentication: boolean;
};

const props = defineProps<Props>();
const page = usePage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Authentication', href: authenticationRoute.index() },
        ],
    },
});

const query = reactive({
    search: props.filters.search ?? '',
    organization_id: props.filters.organization_id ?? '',
    status: props.filters.status ?? '',
    group: props.filters.group ?? '',
    role_id: props.filters.role_id ?? '',
});

const userDrawerOpen = ref(false);
const passwordDrawerOpen = ref(false);
const roleDrawerOpen = ref(false);
const editingUser = ref<AdminUser | null>(null);
const passwordUser = ref<AdminUser | null>(null);

const userForm = useForm<UserForm>({
    name: '',
    email: '',
    password: '',
    organization_ids: [],
    is_active: true,
    photo: null,
});

const passwordForm = useForm({
    password: '',
});

const roleForm = useForm<RoleForm>({
    name: '',
    description: '',
    stay_on_authentication: true,
});

const canSee = (permission: string): boolean =>
    page.props.auth.permissions[permission] === true;

const initials = (name: string): string =>
    name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');

const generatedPassword = (): string => {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    const values = crypto.getRandomValues(new Uint32Array(18));

    return Array.from(values, (value) => alphabet[value % alphabet.length]).join('');
};

const tabHref = (tab: AuthenticationTab['key']) =>
    authenticationRoute.index({ query: { tab } });

const search = (): void => {
    router.get(
        authenticationRoute.index().url,
        {
            tab: props.activeTab,
            search: query.search,
            organization_id: query.organization_id,
            status: query.status,
            group: query.group,
            role_id: query.role_id,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const reset = (): void => {
    query.search = '';
    query.organization_id = '';
    query.status = '';
    query.group = '';
    query.role_id = '';
    search();
};

const isAssigned = (permissionId: number): boolean =>
    props.payload.matrix?.assignedPermissionIds.includes(permissionId) ?? false;

const openCreateUser = (): void => {
    editingUser.value = null;
    userForm.reset();
    userForm.clearErrors();
    userForm.organization_ids = [];
    userForm.is_active = true;
    userDrawerOpen.value = true;
};

const openEditUser = (user: AdminUser): void => {
    editingUser.value = user;
    userForm.reset();
    userForm.clearErrors();
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.password = '';
    userForm.organization_ids = user.organizations.map((organization) => organization.id);
    userForm.is_active = user.is_active;
    userForm.photo = null;
    userDrawerOpen.value = true;
};

const submitUser = (): void => {
    if (editingUser.value) {
        userForm
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(usersRoute.update(editingUser.value).url, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    userDrawerOpen.value = false;
                    userForm.reset();
                },
            });

        return;
    }

    userForm
        .transform((data) => data)
        .post(usersRoute.store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                userDrawerOpen.value = false;
                userForm.reset();
            },
        });
};

const onPhotoChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    userForm.photo = input.files?.[0] ?? null;
};

const openPasswordDrawer = (user: AdminUser): void => {
    passwordUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    passwordDrawerOpen.value = true;
};

const openCreateRole = (): void => {
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.stay_on_authentication = true;
    roleDrawerOpen.value = true;
};

const submitRole = (): void => {
    roleForm.post(rolesRoute.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            roleDrawerOpen.value = false;
            roleForm.reset();
        },
    });
};

const submitPassword = (): void => {
    if (!passwordUser.value) {
        return;
    }

    passwordForm.put(usersRoute.changePassword(passwordUser.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            passwordDrawerOpen.value = false;
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Authentication" />

    <AdminPage
        title="Authentication"
        description="Manage users, roles, role permissions, and permission catalog."
    >
        <div class="rounded-lg border bg-card">
            <div class="border-b px-3 pt-3">
                <div class="flex gap-1 overflow-x-auto">
                    <Button
                        v-for="tab in tabs.filter((item) => canSee(item.permission))"
                        :key="tab.key"
                        as-child
                        size="sm"
                        :variant="activeTab === tab.key ? 'default' : 'ghost'"
                        class="shrink-0 mb-2"
                    >
                        <Link :href="tabHref(tab.key)">{{ tab.label }}</Link>
                    </Button>
                </div>
            </div>

            <div class="border-b p-3">
                <AdminToolbar
                    v-model:search="query.search"
                    :placeholder="`Search ${activeTab.replace('-', ' ')}`"
                    @submit="search"
                    @reset="reset"
                >
                    <template v-if="activeTab === 'users'">
                        <select
                            v-model="query.organization_id"
                            class="h-8 rounded-md border bg-background px-2 text-[13px]"
                        >
                            <option value="">All organizations</option>
                            <option
                                v-for="organization in payload.organizations ?? []"
                                :key="organization.id"
                                :value="String(organization.id)"
                            >
                                {{ organization.name }}
                            </option>
                        </select>
                        <select
                            v-model="query.status"
                            class="h-8 rounded-md border bg-background px-2 text-[13px]"
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="locked">Locked</option>
                        </select>
                    </template>

                    <select
                        v-if="activeTab === 'permissions'"
                        v-model="query.group"
                        class="h-8 rounded-md border bg-background px-2 text-[13px]"
                    >
                        <option value="">All groups</option>
                        <option
                            v-for="group in payload.groups ?? []"
                            :key="group"
                            :value="group"
                        >
                            {{ group }}
                        </option>
                    </select>

                    <select
                        v-if="activeTab === 'role-permissions'"
                        v-model="query.role_id"
                        class="h-8 rounded-md border bg-background px-2 text-[13px]"
                        @change="search"
                    >
                        <option value="">Select role</option>
                        <option
                            v-for="role in payload.matrix?.roles ?? []"
                            :key="role.id"
                            :value="String(role.id)"
                        >
                            {{ role.name }}
                        </option>
                    </select>

                    <Button
                        v-if="activeTab === 'roles' && canSee('access.roles.create')"
                        size="sm"
                        type="button"
                        @click="openCreateRole"
                    >
                        <Plus class="size-3.5" />
                        Role
                    </Button>

                    <Button
                        v-if="activeTab === 'users' && canSee('users.create')"
                        size="sm"
                        type="button"
                        @click="openCreateUser"
                    >
                        <Plus class="size-3.5" />
                        User
                    </Button>
                </AdminToolbar>
            </div>

            <div v-if="activeTab === 'users'">
                <div v-if="payload.users?.data.length" class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead class="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">User</th>
                                <th class="px-3 py-2 font-medium">Organizations</th>
                                <th class="px-3 py-2 font-medium">Roles</th>
                                <th class="px-3 py-2 font-medium">Special Permissions</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in payload.users.data"
                                :key="user.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <Avatar class="size-8">
                                            <AvatarImage
                                                v-if="user.avatar"
                                                :src="user.avatar"
                                                :alt="user.name"
                                            />
                                            <AvatarFallback class="text-xs">
                                                {{ initials(user.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">{{ user.name }}</p>
                                            <p class="truncate text-xs text-muted-foreground">
                                                {{ user.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="organization in user.organizations"
                                            :key="organization.id"
                                            variant="secondary"
                                        >
                                            {{ organization.name }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="role in user.roles ?? []"
                                            :key="role.id"
                                            variant="outline"
                                        >
                                            {{ role.name }}
                                        </Badge>
                                        <span v-if="!(user.roles ?? []).length" class="text-xs text-muted-foreground italic">—</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="permission in user.permissions ?? []"
                                            :key="permission.id"
                                            variant="outline"
                                            class="border-amber-200 bg-amber-50/50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-400"
                                        >
                                            {{ permission.name }}
                                        </Badge>
                                        <span v-if="!(user.permissions ?? []).length" class="text-xs text-muted-foreground italic">—</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <Badge
                                        :variant="
                                            user.locked_at
                                                ? 'destructive'
                                                : user.is_active
                                                  ? 'default'
                                                  : 'secondary'
                                        "
                                    >
                                        {{
                                            user.locked_at
                                                ? 'Locked'
                                                : user.is_active
                                                  ? 'Active'
                                                  : 'Inactive'
                                        }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button size="icon" variant="ghost" class="size-8">
                                                <Ellipsis class="size-4" />
                                                <span class="sr-only">Open actions</span>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-44">
                                            <DropdownMenuLabel>Controls</DropdownMenuLabel>
                                            <DropdownMenuItem as-child>
                                                <Link :href="userAccessRoute.edit(user)">
                                                    <ShieldCheck class="size-3.5" />
                                                    Access
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canSee('users.update')"
                                                @click="openEditUser(user)"
                                            >
                                                <Pencil class="size-3.5" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canSee('users.change-password')"
                                                @click="openPasswordDrawer(user)"
                                            >
                                                <KeyRound class="size-3.5" />
                                                Change pass
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="!user.locked_at"
                                                @click="router.post(usersRoute.lock(user).url)"
                                            >
                                                <Lock class="size-3.5" />
                                                Lock
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-else
                                                @click="router.post(usersRoute.unlock(user).url)"
                                            >
                                                <Unlock class="size-3.5" />
                                                Unlock
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator v-if="canSee('users.delete')" />
                                            <ConfirmAction
                                                v-if="canSee('users.delete')"
                                                title="Delete user?"
                                                description="This user account and organization membership will be removed. This action cannot be undone."
                                                confirm-label="Delete"
                                                @confirm="
                                                    router.delete(usersRoute.destroy(user).url, {
                                                        preserveScroll: true,
                                                    })
                                                "
                                            >
                                                <DropdownMenuItem variant="destructive" @select.prevent>
                                                    <Trash2 class="size-3.5" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </ConfirmAction>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-3">
                    <EmptyState
                        title="No users found"
                        description="Users are loaded only when this tab is opened, with filters handled by the server."
                    />
                </div>
                <AdminPagination
                    v-if="payload.users"
                    :links="payload.users.links"
                    :from="payload.users.from"
                    :to="payload.users.to"
                    :total="payload.users.total"
                />
            </div>

            <Sheet v-model:open="userDrawerOpen">
                <SheetContent class="w-full overflow-y-auto p-0 sm:max-w-md">
                    <SheetHeader class="border-b px-4 py-3 text-left">
                        <SheetTitle>{{ editingUser ? 'Edit user' : 'Add new user' }}</SheetTitle>
                        <SheetDescription>
                            Manage account details, photo, and organization membership.
                        </SheetDescription>
                    </SheetHeader>

                    <form class="grid gap-3 px-4 py-3 text-[13px]" @submit.prevent="submitUser">
                        <div class="grid gap-1.5">
                            <Label for="user-photo">Photo</Label>
                            <Input id="user-photo" type="file" accept="image/*" @change="onPhotoChange" />
                            <p v-if="userForm.errors.photo" class="text-xs text-destructive">
                                {{ userForm.errors.photo }}
                            </p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="user-name">Name</Label>
                            <Input id="user-name" v-model="userForm.name" required />
                            <p v-if="userForm.errors.name" class="text-xs text-destructive">
                                {{ userForm.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="user-email">Email</Label>
                            <Input id="user-email" v-model="userForm.email" type="email" required />
                            <p v-if="userForm.errors.email" class="text-xs text-destructive">
                                {{ userForm.errors.email }}
                            </p>
                        </div>

                        <div v-if="!editingUser" class="grid gap-1.5">
                            <Label for="user-password">Password</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="user-password"
                                    v-model="userForm.password"
                                    autocomplete="new-password"
                                    type="text"
                                />
                                <Button
                                    size="icon"
                                    type="button"
                                    variant="outline"
                                    @click="userForm.password = generatedPassword()"
                                >
                                    <WandSparkles class="size-4" />
                                    <span class="sr-only">Generate password</span>
                                </Button>
                            </div>
                            <p v-if="userForm.errors.password" class="text-xs text-destructive">
                                {{ userForm.errors.password }}
                            </p>
                        </div>

                        <label
                            v-if="editingUser"
                            class="flex items-center gap-2 rounded-md border px-3 py-2"
                        >
                            <input v-model="userForm.is_active" type="checkbox" />
                            Active account
                        </label>

                        <div class="grid gap-1.5">
                            <Label>Organizations</Label>
                            <div class="grid max-h-44 gap-1 overflow-y-auto rounded-md border p-2">
                                <label
                                    v-for="organization in payload.organizations ?? []"
                                    :key="organization.id"
                                    class="flex items-center gap-2 rounded px-2 py-1"
                                >
                                    <input
                                        v-model="userForm.organization_ids"
                                        type="checkbox"
                                        :value="organization.id"
                                    />
                                    {{ organization.name }}
                                </label>
                            </div>
                            <p
                                v-if="userForm.errors.organization_ids"
                                class="text-xs text-destructive"
                            >
                                {{ userForm.errors.organization_ids }}
                            </p>
                        </div>

                        <SheetFooter class="mt-1 flex-row justify-end gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                @click="userDrawerOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="userForm.processing">
                                {{ editingUser ? 'Save changes' : 'Create user' }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <Sheet v-model:open="passwordDrawerOpen">
                <SheetContent class="w-full p-0 sm:max-w-sm">
                    <SheetHeader class="border-b px-4 py-3 text-left">
                        <SheetTitle>Change password</SheetTitle>
                        <SheetDescription>
                            Set a new password for {{ passwordUser?.name ?? 'this user' }}.
                        </SheetDescription>
                    </SheetHeader>

                    <form class="grid gap-3 px-4 py-3 text-[13px]" @submit.prevent="submitPassword">
                        <div class="grid gap-1.5">
                            <Label for="change-password">Password</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="change-password"
                                    v-model="passwordForm.password"
                                    autocomplete="new-password"
                                    required
                                    type="text"
                                />
                                <Button
                                    size="icon"
                                    type="button"
                                    variant="outline"
                                    @click="passwordForm.password = generatedPassword()"
                                >
                                    <WandSparkles class="size-4" />
                                    <span class="sr-only">Generate password</span>
                                </Button>
                            </div>
                            <p v-if="passwordForm.errors.password" class="text-xs text-destructive">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <SheetFooter class="mt-1 flex-row justify-end gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                @click="passwordDrawerOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="passwordForm.processing">
                                Change password
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <div v-if="activeTab === 'roles'">
                <div
                    v-if="payload.roles && 'data' in payload.roles && payload.roles.data.length"
                    class="overflow-x-auto"
                >
                    <table class="w-full text-left text-[13px]">
                        <thead class="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">Role</th>
                                <th class="px-3 py-2 font-medium">Users</th>
                                <th class="px-3 py-2 font-medium">Permissions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="role in payload.roles.data"
                                :key="role.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2">
                                    <p class="font-medium">{{ role.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ role.description ?? 'No description' }}
                                    </p>
                                </td>
                                <td class="px-3 py-2">{{ role.users_count ?? 0 }}</td>
                                <td class="px-3 py-2">{{ role.permissions_count ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-3">
                    <EmptyState
                        title="No roles found"
                        description="Roles are loaded server-side after opening this tab."
                    />
                </div>
                <AdminPagination
                    v-if="payload.roles && 'data' in payload.roles"
                    :links="payload.roles.links"
                    :from="payload.roles.from"
                    :to="payload.roles.to"
                    :total="payload.roles.total"
                />

                <Sheet v-model:open="roleDrawerOpen">
                    <SheetContent class="w-full p-0 sm:max-w-sm">
                        <SheetHeader class="border-b px-4 py-3 text-left">
                            <SheetTitle>Add new role</SheetTitle>
                            <SheetDescription>
                                Create an organization-scoped role for this authentication workspace.
                            </SheetDescription>
                        </SheetHeader>

                        <form class="grid gap-3 px-4 py-3 text-[13px]" @submit.prevent="submitRole">
                            <div class="grid gap-1.5">
                                <Label for="role-name">Name</Label>
                                <Input id="role-name" v-model="roleForm.name" required />
                                <p v-if="roleForm.errors.name" class="text-xs text-destructive">
                                    {{ roleForm.errors.name }}
                                </p>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="role-description">Description</Label>
                                <Input id="role-description" v-model="roleForm.description" />
                                <p
                                    v-if="roleForm.errors.description"
                                    class="text-xs text-destructive"
                                >
                                    {{ roleForm.errors.description }}
                                </p>
                            </div>

                            <SheetFooter class="mt-1 flex-row justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    @click="roleDrawerOpen = false"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="roleForm.processing">
                                    Create role
                                </Button>
                            </SheetFooter>
                        </form>
                    </SheetContent>
                </Sheet>
            </div>

            <div v-if="activeTab === 'role-permissions'" class="p-3">
                <div v-if="payload.matrix?.selectedRole" class="grid gap-3">
                    <div
                        v-for="(permissions, group) in payload.matrix.permissionGroups"
                        :key="group"
                        class="rounded-md border"
                    >
                        <div class="border-b bg-muted/40 px-3 py-2">
                            <span class="text-sm font-medium">{{ group }}</span>
                            <Badge class="ml-2" variant="secondary">{{ permissions.length }}</Badge>
                        </div>
                        <div class="grid gap-1 p-2 md:grid-cols-2 lg:grid-cols-4">
                            <label
                                v-for="permission in permissions"
                                :key="permission.id"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px]"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isAssigned(permission.id)"
                                    disabled
                                />
                                {{ permission.name }}
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <Button as-child size="sm" variant="outline">
                            <Link
                                :href="
                                    rolePermissionsRoute.index({
                                        query: {
                                            role_id: payload.matrix.selectedRole.id,
                                        },
                                    })
                                "
                            >
                                Manage matrix
                            </Link>
                        </Button>
                    </div>
                </div>
                <EmptyState
                    v-else
                    title="Select a role"
                    description="Role permission data is loaded by the server only after a role is selected."
                />
            </div>

            <div v-if="activeTab === 'permissions'">
                <div v-if="payload.permissions?.data.length" class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead class="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">Permission</th>
                                <th class="px-3 py-2 font-medium">Group</th>
                                <th class="px-3 py-2 font-medium">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="permission in payload.permissions.data"
                                :key="permission.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2 font-medium">{{ permission.name }}</td>
                                <td class="px-3 py-2">
                                    <Badge variant="secondary">{{ permission.group }}</Badge>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ permission.description ?? 'No description' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-3">
                    <EmptyState
                        title="No permissions found"
                        description="Permission data is loaded only after opening this tab."
                    />
                </div>
                <AdminPagination
                    v-if="payload.permissions"
                    :links="payload.permissions.links"
                    :from="payload.permissions.from"
                    :to="payload.permissions.to"
                    :total="payload.permissions.total"
                />
            </div>
        </div>
    </AdminPage>
</template>
