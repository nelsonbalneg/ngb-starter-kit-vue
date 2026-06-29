<script setup lang="ts">
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

type ButtonVariant = 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';

type Props = {
    icon: Component;
    label: string;
    variant?: ButtonVariant;
    disabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    variant: 'ghost',
    disabled: false,
});

defineEmits<{
    click: [];
}>();
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    size="icon"
                    :variant="variant"
                    :disabled="disabled"
                    :aria-label="label"
                    class="size-7"
                    @click="$emit('click')"
                >
                    <component :is="icon" class="size-3.5" />
                </Button>
            </TooltipTrigger>
            <TooltipContent side="top">
                <p class="text-xs">{{ label }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
