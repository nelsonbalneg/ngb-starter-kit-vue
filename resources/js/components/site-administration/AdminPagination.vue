<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types/site-administration';

type Props = {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-2 border-t px-3 py-2 text-xs text-muted-foreground md:flex-row md:items-center md:justify-between">
        <span>Showing {{ from ?? 0 }} to {{ to ?? 0 }} of {{ total }} records</span>
        <div class="flex flex-wrap gap-1">
            <template v-for="link in links" :key="link.label">
                <Button
                    v-if="link.url"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                >
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <Button v-else size="sm" variant="ghost" disabled v-html="link.label" />
            </template>
        </div>
    </div>
</template>
