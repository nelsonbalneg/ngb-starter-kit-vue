<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

type Props = {
    title: string;
    description: string;
    processing?: boolean;
    confirmLabel?: string;
};

defineProps<Props>();

defineEmits<{
    confirm: [];
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="$emit('confirm')"
                >
                    <Trash2 class="size-3.5" />
                    {{ confirmLabel ?? 'Confirm' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
