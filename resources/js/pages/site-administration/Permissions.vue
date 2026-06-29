<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive, ref } from 'vue';
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
import permissionsRoute from '@/routes/site-administration/permissions';
import type { Paginated, Permission } from '@/types';

type Props = {
    permissions: Paginated<Permission>;
    groups: string[];
    filters: { search?: string; group?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Permissions', href: permissionsRoute.index() },
        ],
    },
});

const query = reactive({ search: props.filters.search ?? '', group: props.filters.group ?? '' });
const editing = ref<Permission | null>(null);
const form = useForm({ name: '', group: '', description: '' });

const openCreate = (): void => {
    editing.value = null;
    form.reset();
    form.clearErrors();
};

const openEdit = (permission: Permission): void => {
    editing.value = permission;
    form.name = permission.name;
    form.group = permission.group;
    form.description = permission.description ?? '';
    form.clearErrors();
};

const submit = (): void => {
    if (editing.value) {
        form.put(permissionsRoute.update(editing.value).url);
        return;
    }
    form.post(permissionsRoute.store().url);
};

const search = (): void => {
    router.get(permissionsRoute.index().url, query, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Permissions" />
    <AdminPage title="Permissions" description="Maintain reusable permission names and groups used by policies, gates, and navigation.">
        <div class="rounded-lg border bg-card">
            <div class="flex flex-col gap-2 border-b p-3 md:flex-row md:items-center md:justify-between">
                <AdminToolbar v-model:search="query.search" placeholder="Search permissions" @submit="search" @reset="() => { query.search = ''; query.group = ''; search(); }">
                    <select v-model="query.group" class="h-8 rounded-md border bg-background px-2 text-[13px]">
                        <option value="">All groups</option>
                        <option v-for="group in groups" :key="group" :value="group">{{ group }}</option>
                    </select>
                </AdminToolbar>
                <Dialog>
                    <DialogTrigger as-child><Button @click="openCreate"><Plus class="size-4" /> Permission</Button></DialogTrigger>
                    <DialogContent>
                        <DialogHeader><DialogTitle>{{ editing ? 'Edit permission' : 'Create permission' }}</DialogTitle></DialogHeader>
                        <form class="grid gap-3" @submit.prevent="submit">
                            <div class="grid gap-1.5"><Label>Name</Label><Input v-model="form.name" placeholder="employee.view" required /><InputError :message="form.errors.name" /></div>
                            <div class="grid gap-1.5"><Label>Group</Label><Input v-model="form.group" placeholder="Employee" required /><InputError :message="form.errors.group" /></div>
                            <div class="grid gap-1.5"><Label>Description</Label><Input v-model="form.description" /><InputError :message="form.errors.description" /></div>
                            <DialogFooter><Button type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create' }}</Button></DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
            <div v-if="permissions.data.length" class="overflow-x-auto">
                <table class="w-full text-left text-[13px]">
                    <thead class="border-b bg-muted/40 text-xs text-muted-foreground"><tr><th class="px-3 py-2 font-medium">Permission</th><th class="px-3 py-2 font-medium">Group</th><th class="px-3 py-2 font-medium">Description</th><th class="px-3 py-2 text-right font-medium">Actions</th></tr></thead>
                    <tbody>
                        <tr v-for="permission in permissions.data" :key="permission.id" class="border-b last:border-0">
                            <td class="px-3 py-2 font-medium">{{ permission.name }}</td>
                            <td class="px-3 py-2"><Badge variant="secondary">{{ permission.group }}</Badge></td>
                            <td class="px-3 py-2 text-muted-foreground">{{ permission.description ?? 'No description' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1.5">
                                    <Dialog>
                                        <DialogTrigger as-child><Button size="sm" variant="outline" @click="openEdit(permission)">Edit</Button></DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader><DialogTitle>Edit permission</DialogTitle></DialogHeader>
                                            <form class="grid gap-3" @submit.prevent="submit">
                                                <div class="grid gap-1.5"><Label>Name</Label><Input v-model="form.name" required /><InputError :message="form.errors.name" /></div>
                                                <div class="grid gap-1.5"><Label>Group</Label><Input v-model="form.group" required /><InputError :message="form.errors.group" /></div>
                                                <div class="grid gap-1.5"><Label>Description</Label><Input v-model="form.description" /></div>
                                                <DialogFooter><Button type="submit" :disabled="form.processing">Save changes</Button></DialogFooter>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                    <ConfirmAction title="Delete permission?" description="This permission will be removed from roles and direct user assignments." confirm-label="Delete" @confirm="router.delete(permissionsRoute.destroy(permission).url)">
                                        <Button size="sm" variant="destructive">Delete</Button>
                                    </ConfirmAction>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-3"><EmptyState title="No permissions found" description="Create action-based permissions such as employee.view, employee.create, employee.update, and employee.delete." /></div>
            <AdminPagination :links="permissions.links" :from="permissions.from" :to="permissions.to" :total="permissions.total" />
        </div>
    </AdminPage>
</template>

