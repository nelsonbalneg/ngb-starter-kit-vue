<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card dark:bg-card/90 text-card-foreground flex flex-col gap-4 rounded-lg border border-border/75 py-4 shadow-sm shadow-slate-900/[0.03] dark:shadow-slate-900/5 dark:backdrop-blur dark:supports-[backdrop-filter]:bg-card/85',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
