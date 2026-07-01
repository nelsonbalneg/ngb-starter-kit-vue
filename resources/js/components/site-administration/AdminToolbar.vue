<script setup lang="ts">
import { RotateCcw, Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    search: string;
    placeholder?: string;
};

defineProps<Props>();

defineEmits<{
    'update:search': [value: string];
    submit: [];
    reset: [];
}>();
</script>

<template>
    <form class="flex flex-col gap-2 md:flex-row md:items-center" @submit.prevent="$emit('submit')">
        <div class="relative md:w-72">
            <Search class="pointer-events-none absolute top-2 left-2 size-4 text-muted-foreground" />
            <Input
                :model-value="search"
                :placeholder="placeholder ?? 'Search...'"
                class="pl-8"
                @update:model-value="$emit('update:search', String($event))"
            />
        </div>
        <slot />
        <div class="flex gap-2">
            <Button type="submit" variant="secondary">
                <Search class="size-3.5" />
                Search
            </Button>
            <Button type="button" variant="ghost" @click="$emit('reset')">
                <RotateCcw class="size-3.5" />
                Reset
            </Button>
        </div>
    </form>
</template>
