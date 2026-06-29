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
import rolesRoute from '@/routes/site-administration/roles';
import type { Paginated, Role } from '@/types';

type Props = {
    roles: Paginated<Role>;
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Roles', href: rolesRoute.index() },
        ],
    },
});

const query = reactive({ search: props.filters.search ?? '' });
const editing = ref<Role | null>(null);
const form = useForm({ name: '', description: '' });

const openCreate = (): void => {
    editing.value = null;
    form.reset();
    form.clearErrors();
};

const openEdit = (role: Role): void => {
    editing.value = role;
    form.name = role.name;
    form.description = role.description ?? '';
    form.clearErrors();
};

const submit = (): void => {
    if (editing.value) {
        form.put(rolesRoute.update(editing.value).url);
        return;
    }

    form.post(rolesRoute.store().url);
};

const search = (): void => {
    router.get(rolesRoute.index().url, query, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Roles" />

    <AdminPage title="Roles" description="Manage organization-scoped roles backed by Spatie Teams.">
        <div class="rounded-lg border bg-card">
            <div class="flex flex-col gap-2 border-b p-3 md:flex-row md:items-center md:justify-between">
                <AdminToolbar
                    v-model:search="query.search"
                    placeholder="Search roles"
                    @submit="search"
                    @reset="() => { query.search = ''; search(); }"
                />

                <Dialog>
                    <DialogTrigger as-child>
                        <Button @click="openCreate"><Plus class="size-4" /> Role</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader><DialogTitle>{{ editing ? 'Edit role' : 'Create role' }}</DialogTitle></DialogHeader>
                        <form class="grid gap-3" @submit.prevent="submit">
                            <div class="grid gap-1.5">
                                <Label>Name</Label>
                                <Input v-model="form.name" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label>Description</Label>
                                <Input v-model="form.description" />
                                <InputError :message="form.errors.description" />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="ghost" @click="form.reset()">Reset</Button>
                                <Button type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create' }}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-if="roles.data.length" class="overflow-x-auto">
                <table class="w-full text-left text-[13px]">
                    <thead class="border-b bg-muted/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 font-medium">Role</th>
                            <th class="px-3 py-2 font-medium">Users</th>
                            <th class="px-3 py-2 font-medium">Permissions</th>
                            <th class="px-3 py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="role in roles.data" :key="role.id" class="border-b last:border-0">
                            <td class="px-3 py-2">
                                <p class="font-medium">{{ role.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ role.description ?? 'No description' }}</p>
                            </td>
                            <td class="px-3 py-2"><Badge variant="secondary">{{ role.users_count ?? 0 }}</Badge></td>
                            <td class="px-3 py-2"><Badge variant="secondary">{{ role.permissions_count ?? 0 }}</Badge></td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1.5">
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button size="sm" variant="outline" @click="openEdit(role)">Edit</Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader><DialogTitle>Edit role</DialogTitle></DialogHeader>
                                            <form class="grid gap-3" @submit.prevent="submit">
                                                <div class="grid gap-1.5">
                                                    <Label>Name</Label>
                                                    <Input v-model="form.name" required />
                                                    <InputError :message="form.errors.name" />
                                                </div>
                                                <div class="grid gap-1.5">
                                                    <Label>Description</Label>
                                                    <Input v-model="form.description" />
                                                </div>
                                                <DialogFooter><Button type="submit" :disabled="form.processing">Save changes</Button></DialogFooter>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                    <ConfirmAction title="Delete role?" description="Users assigned to this role will lose the role permissions." confirm-label="Delete" @confirm="router.delete(rolesRoute.destroy(role).url)">
                                        <Button size="sm" variant="destructive">Delete</Button>
                                    </ConfirmAction>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-3">
                <EmptyState title="No roles found" description="Create roles for this organization and assign permissions through the role matrix." />
            </div>
            <AdminPagination :links="roles.links" :from="roles.from" :to="roles.to" :total="roles.total" />
        </div>
    </AdminPage>
</template>

