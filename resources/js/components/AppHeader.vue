<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Building2,
    ChevronDown,
    Cog,
    LayoutGrid,
    ListTree,
    Menu,
    Settings,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import authentication from '@/routes/site-administration/authentication';
import lookups from '@/routes/site-administration/lookups';
import organizations from '@/routes/site-administration/organizations';
import settings from '@/routes/site-administration/settings';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

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

const canAccess = (item: NavItem): boolean => {
    if (item.children?.length) {
        return item.children.some(canAccess);
    }

    return (
        !item.permission ||
        page.props.auth.permissions[item.permission] === true
    );
};

const visibleItems = computed(() => mainNavItems.filter(canAccess));

const visibleChildren = (item: NavItem): NavItem[] =>
    item.children?.filter(canAccess) ?? [];

const isItemActive = (item: NavItem): boolean =>
    isCurrentUrl(item.href) || item.children?.some(isItemActive) === true;
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-12 items-center px-3 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-1.5 h-8 w-8"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[260px] p-4">
                            <SheetTitle class="sr-only"
                                >Navigation menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-5 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-3 py-4"
                            >
                                <nav class="-mx-2 space-y-0.5">
                                    <Link
                                        v-for="item in visibleItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-2 rounded-md px-2 py-1.5 text-[13px] font-medium hover:bg-accent"
                                        :class="
                                            whenCurrentUrl(
                                                item.href,
                                                activeItemStyles,
                                            )
                                        "
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-4 w-4"
                                        />
                                        {{ item.title }}
                                    </Link>

                                    <template
                                        v-for="item in visibleItems.filter(
                                            (entry) => entry.children?.length,
                                        )"
                                        :key="`${item.title}-children`"
                                    >
                                        <p
                                            class="px-2 pt-3 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ item.title }}
                                        </p>
                                        <Link
                                            v-for="child in visibleChildren(
                                                item,
                                            )"
                                            :key="child.title"
                                            :href="child.href"
                                            class="flex items-center gap-x-2 rounded-md px-2 py-1.5 text-[13px] font-medium hover:bg-accent"
                                            :class="
                                                whenCurrentUrl(
                                                    child.href,
                                                    activeItemStyles,
                                                )
                                            "
                                        >
                                            <component
                                                v-if="child.icon"
                                                :is="child.icon"
                                                class="h-4 w-4"
                                            />
                                            {{ child.title }}
                                        </Link>
                                    </template>
                                </nav>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-1.5">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-6 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-1"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in visibleItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <DropdownMenu v-if="item.children?.length">
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            :class="[
                                                navigationMenuTriggerStyle(),
                                                isItemActive(item)
                                                    ? activeItemStyles
                                                    : null,
                                                'h-8 cursor-pointer px-2.5 text-[13px]',
                                            ]"
                                        >
                                            <component
                                                v-if="item.icon"
                                                :is="item.icon"
                                                class="mr-1.5 h-3.5 w-3.5"
                                            />
                                            {{ item.title }}
                                            <ChevronDown
                                                class="ml-1 h-3.5 w-3.5 opacity-70"
                                            />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="start"
                                        class="w-52"
                                    >
                                        <DropdownMenuItem
                                            v-for="child in visibleChildren(
                                                item,
                                            )"
                                            :key="child.title"
                                            as-child
                                        >
                                            <Link :href="child.href">
                                                <component
                                                    v-if="child.icon"
                                                    :is="child.icon"
                                                />
                                                {{ child.title }}
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <Link
                                    v-else
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        isItemActive(item)
                                            ? activeItemStyles
                                            : null,
                                        'h-8 cursor-pointer px-2.5 text-[13px]',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-1.5 h-3.5 w-3.5"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isItemActive(item)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-1.5">
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-8 w-auto rounded-full p-0.5 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-7 overflow-hidden rounded-full text-xs"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-9 w-full items-center justify-start px-3 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
