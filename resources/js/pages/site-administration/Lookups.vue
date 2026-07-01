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
import lookupsRoute from '@/routes/site-administration/lookups';
import organizationsRoute from '@/routes/site-administration/organizations';
import type { Lookup, Paginated } from '@/types';

type Props = { lookups: Paginated<Lookup>; groups: string[]; filters: { search?: string; group?: string; status?: string } };
const props = defineProps<Props>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Site Administration', href: organizationsRoute.index() }, { title: 'Lookups', href: lookupsRoute.index() }] } });

const query = reactive({ search: props.filters.search ?? '', group: props.filters.group ?? '', status: props.filters.status ?? '' });
const editing = ref<Lookup | null>(null);
const form = useForm({ group: '', code: '', name: '', description: '', sort_order: 0, is_active: true });

const openCreate = (): void => { editing.value = null; form.reset(); form.is_active = true; form.clearErrors(); };
const openEdit = (lookup: Lookup): void => {
    editing.value = lookup;
    form.group = lookup.group; form.code = lookup.code; form.name = lookup.name;
    form.description = lookup.description ?? ''; form.sort_order = lookup.sort_order; form.is_active = lookup.is_active;
    form.clearErrors();
};
const submit = (): void => {
    if (editing.value) { form.put(lookupsRoute.update(editing.value).url); return; }
    form.post(lookupsRoute.store().url);
};
const search = (): void => { router.get(lookupsRoute.index().url, query, { preserveState: true, replace: true }); };
</script>

<template>
    <Head title="Lookups" />
    <AdminPage title="Lookups" description="Maintain reusable lookup values for addresses, profiles, HR records, and application modules.">
        <div class="rounded-lg border bg-card">
            <div class="flex flex-col gap-2 border-b p-3 md:flex-row md:items-center md:justify-between">
                <AdminToolbar v-model:search="query.search" placeholder="Search lookups" @submit="search" @reset="() => { query.search = ''; query.group = ''; query.status = ''; search(); }">
                    <select v-model="query.group" class="h-9 rounded-md border bg-background px-3 text-sm"><option value="">All groups</option><option v-for="group in groups" :key="group" :value="group">{{ group }}</option></select>
                    <select v-model="query.status" class="h-9 rounded-md border bg-background px-3 text-sm"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
                </AdminToolbar>
                <Dialog>
                    <DialogTrigger as-child><Button @click="openCreate"><Plus class="size-4" /> Lookup</Button></DialogTrigger>
                    <DialogContent>
                        <DialogHeader><DialogTitle>{{ editing ? 'Edit lookup' : 'Create lookup' }}</DialogTitle></DialogHeader>
                        <form class="grid gap-3" @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="grid gap-1.5"><Label>Group</Label><Input v-model="form.group" required /><InputError :message="form.errors.group" /></div>
                                <div class="grid gap-1.5"><Label>Code</Label><Input v-model="form.code" required /><InputError :message="form.errors.code" /></div>
                                <div class="grid gap-1.5"><Label>Name</Label><Input v-model="form.name" required /><InputError :message="form.errors.name" /></div>
                                <div class="grid gap-1.5"><Label>Sort</Label><Input v-model="form.sort_order" type="number" min="0" /></div>
                            </div>
                            <div class="grid gap-1.5"><Label>Description</Label><Input v-model="form.description" /></div>
                            <label class="flex items-center gap-2 text-[13px]"><input v-model="form.is_active" type="checkbox" /> Active</label>
                            <DialogFooter><Button type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create' }}</Button></DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
            <div v-if="lookups.data.length" class="overflow-x-auto">
                <table class="w-full text-left text-[13px]">
                    <thead class="border-b bg-muted/40 text-xs text-muted-foreground"><tr><th class="px-3 py-2 font-medium">Lookup</th><th class="px-3 py-2 font-medium">Group</th><th class="px-3 py-2 font-medium">Sort</th><th class="px-3 py-2 font-medium">Status</th><th class="px-3 py-2 text-right font-medium">Actions</th></tr></thead>
                    <tbody><tr v-for="lookup in lookups.data" :key="lookup.id" class="border-b last:border-0">
                        <td class="px-3 py-2"><p class="font-medium">{{ lookup.name }}</p><p class="text-xs text-muted-foreground">{{ lookup.code }}</p></td>
                        <td class="px-3 py-2"><Badge variant="secondary">{{ lookup.group }}</Badge></td>
                        <td class="px-3 py-2">{{ lookup.sort_order }}</td>
                        <td class="px-3 py-2"><Badge :variant="lookup.is_active ? 'default' : 'secondary'">{{ lookup.is_active ? 'Active' : 'Inactive' }}</Badge></td>
                        <td class="px-3 py-2"><div class="flex justify-end gap-1.5">
                            <Dialog><DialogTrigger as-child><Button size="sm" variant="outline" @click="openEdit(lookup)">Edit</Button></DialogTrigger><DialogContent><DialogHeader><DialogTitle>Edit lookup</DialogTitle></DialogHeader><form class="grid gap-3" @submit.prevent="submit"><div class="grid gap-1.5"><Label>Group</Label><Input v-model="form.group" required /></div><div class="grid gap-1.5"><Label>Code</Label><Input v-model="form.code" required /></div><div class="grid gap-1.5"><Label>Name</Label><Input v-model="form.name" required /></div><label class="flex items-center gap-2 text-[13px]"><input v-model="form.is_active" type="checkbox" /> Active</label><DialogFooter><Button type="submit" :disabled="form.processing">Save changes</Button></DialogFooter></form></DialogContent></Dialog>
                            <ConfirmAction title="Delete lookup?" description="Modules using this lookup may no longer find the value." confirm-label="Delete" @confirm="router.delete(lookupsRoute.destroy(lookup).url)"><Button size="sm" variant="destructive">Delete</Button></ConfirmAction>
                        </div></td>
                    </tr></tbody>
                </table>
            </div>
            <div v-else class="p-3"><EmptyState title="No lookups found" description="Create reusable lookup entries such as countries, cities, civil status, blood types, religion, and nationality." /></div>
            <AdminPagination :links="lookups.links" :from="lookups.from" :to="lookups.to" :total="lookups.total" />
        </div>
    </AdminPage>
</template>

