<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const canAccess = (item: NavItem): boolean => {
    if (item.children?.length) {
        return item.children.some(canAccess);
    }

    return !item.permission || page.props.auth.permissions[item.permission] === true;
};

const visibleItems = computed(() => props.items.filter(canAccess));

const isItemActive = (item: NavItem): boolean =>
    isCurrentUrl(item.href) || item.children?.some(isItemActive) === true;
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in visibleItems" :key="item.title">
                <Collapsible
                    v-if="item.children?.length"
                    :default-open="isItemActive(item)"
                    class="group/collapsible"
                >
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            :is-active="isItemActive(item)"
                            :tooltip="item.title"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem
                                v-for="child in item.children.filter(canAccess)"
                                :key="child.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isCurrentUrl(child.href)"
                                    :class="
                                        whenCurrentUrl(
                                            child.href,
                                            'font-medium text-sidebar-accent-foreground',
                                        )
                                    "
                                >
                                    <Link :href="child.href">
                                        <component
                                            v-if="child.icon"
                                            :is="child.icon"
                                        />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>

                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
