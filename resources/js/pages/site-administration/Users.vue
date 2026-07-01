<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive } from 'vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import AdminPagination from '@/components/site-administration/AdminPagination.vue';
import AdminToolbar from '@/components/site-administration/AdminToolbar.vue';
import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import EmptyState from '@/components/site-administration/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import organizationsRoute from '@/routes/site-administration/organizations';
import userAccessRoute from '@/routes/site-administration/user-access';
import usersRoute from '@/routes/site-administration/users';
import type { AdminUser, Organization, Paginated } from '@/types';

type Props = {
    users: Paginated<AdminUser>;
    organizations: Pick<Organization, 'id' | 'name'>[];
    filters: { search?: string; organization_id?: string; status?: string };
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

const query = reactive({
    search: props.filters.search ?? '',
    organization_id: props.filters.organization_id ?? '',
    status: props.filters.status ?? '',
});

const form = useForm({
    name: '',
    email: '',
    organization_ids: [] as number[],
});

const submit = (): void => {
    form.post(usersRoute.store().url, {
        onSuccess: () => form.reset(),
    });
};

const search = (): void => {
    router.get(usersRoute.index().url, query, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Users" />
    <AdminPage title="Users" description="Invite users, manage account status, and assign organization-scoped access.">
        <div class="rounded-lg border bg-card">
            <div class="flex flex-col gap-2 border-b p-3 md:flex-row md:items-center md:justify-between">
                <AdminToolbar v-model:search="query.search" placeholder="Search users" @submit="search" @reset="() => { query.search = ''; query.organization_id = ''; query.status = ''; search(); }">
                    <select v-model="query.organization_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">All organizations</option>
                        <option v-for="organization in organizations" :key="organization.id" :value="String(organization.id)">{{ organization.name }}</option>
                    </select>
                    <select v-model="query.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="locked">Locked</option>
                    </select>
                </AdminToolbar>
                <Dialog>
                    <DialogTrigger as-child><Button><Plus class="size-4" /> Invite user</Button></DialogTrigger>
                    <DialogContent>
                        <DialogHeader><DialogTitle>Invite user</DialogTitle></DialogHeader>
                        <form class="grid gap-3" @submit.prevent="submit">
                            <div class="grid gap-1.5"><Label>Name</Label><Input v-model="form.name" required /><InputError :message="form.errors.name" /></div>
                            <div class="grid gap-1.5"><Label>Email</Label><Input v-model="form.email" type="email" required /><InputError :message="form.errors.email" /></div>
                            <div class="grid gap-1.5">
                                <Label>Organizations</Label>
                                <label v-for="organization in organizations" :key="organization.id" class="flex items-center gap-2 text-[13px]">
                                    <input v-model="form.organization_ids" type="checkbox" :value="organization.id" />
                                    {{ organization.name }}
                                </label>
                                <InputError :message="form.errors.organization_ids" />
                            </div>
                            <DialogFooter><Button type="submit" :disabled="form.processing">Send invite</Button></DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-if="users.data.length" class="overflow-x-auto">
                <table class="w-full text-left text-[13px]">
                    <thead class="border-b bg-muted/40 text-xs text-muted-foreground"><tr><th class="px-3 py-2 font-medium">User</th><th class="px-3 py-2 font-medium">Organizations</th><th class="px-3 py-2 font-medium">Status</th><th class="px-3 py-2 text-right font-medium">Actions</th></tr></thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-b last:border-0">
                            <td class="px-3 py-2"><p class="font-medium">{{ user.name }}</p><p class="text-xs text-muted-foreground">{{ user.email }}</p></td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="organization in user.organizations" :key="organization.id" variant="secondary">{{ organization.name }}</Badge>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <Badge :variant="user.locked_at ? 'destructive' : user.is_active ? 'default' : 'secondary'">
                                    {{ user.locked_at ? 'Locked' : user.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1.5">
                                    <Button as-child size="sm" variant="outline"><Link :href="userAccessRoute.edit(user)">Access</Link></Button>
                                    <ConfirmAction title="Reset password?" description="A new secure password will be generated for this account." confirm-label="Reset" @confirm="router.post(usersRoute.resetPassword(user).url)">
                                        <Button size="sm" variant="outline">Reset</Button>
                                    </ConfirmAction>
                                    <ConfirmAction v-if="!user.locked_at" title="Lock account?" description="The user will be blocked from accessing the application." confirm-label="Lock" @confirm="router.post(usersRoute.lock(user).url)">
                                        <Button size="sm" variant="destructive">Lock</Button>
                                    </ConfirmAction>
                                    <ConfirmAction v-else title="Unlock account?" description="The account will be restored to active access." confirm-label="Unlock" @confirm="router.post(usersRoute.unlock(user).url)">
                                        <Button size="sm" variant="secondary">Unlock</Button>
                                    </ConfirmAction>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-3"><EmptyState title="No users found" description="Invite users and assign their organization, roles, and direct permissions." /></div>
            <AdminPagination :links="users.links" :from="users.from" :to="users.to" :total="users.total" />
        </div>
    </AdminPage>
</template>

