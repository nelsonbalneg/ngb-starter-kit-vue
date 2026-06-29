<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Building2,
    Cog,
    LayoutGrid,
    ListTree,
    Settings,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import authentication from '@/routes/site-administration/authentication';
import lookups from '@/routes/site-administration/lookups';
import organizations from '@/routes/site-administration/organizations';
import settings from '@/routes/site-administration/settings';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Site Administration',
        href: organizations.index(),
        icon: Cog,
        permission: 'site-administration.view',
        children: [
            {
                title: 'Organizations',
                href: organizations.index(),
                icon: Building2,
                permission: 'access.organizations.view',
            },
            {
                title: 'Authentication',
                href: authentication.index(),
                icon: BadgeCheck,
                permission: 'authentication.view',
            },
            {
                title: 'Lookups',
                href: lookups.index(),
                icon: ListTree,
                permission: 'lookups.view',
            },
            {
                title: 'Site Settings',
                href: settings.index(),
                icon: Settings,
                permission: 'settings.view',
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
