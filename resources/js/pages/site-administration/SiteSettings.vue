<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    Building2,
    Check,
    Code2,
    Image,
    Palette,
    ShieldAlert,
    SlidersHorizontal,
    Table2,
    Type,
    WandSparkles,
} from '@lucide/vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import InputError from '@/components/InputError.vue';
import OrgLogoDropzone from '@/components/site-administration/OrgLogoDropzone.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { applyAppearanceSettings } from '@/composables/useAppearance';
import organizationsRoute from '@/routes/site-administration/organizations';
import settingsRoute from '@/routes/site-administration/settings';
import settingsAppearanceRoute from '@/routes/site-administration/settings/appearance';
import settingsBrandingRoute from '@/routes/site-administration/settings/branding';
import settingsMaintenanceRoute from '@/routes/site-administration/settings/maintenance';
import type { AppearanceSettings } from '@/types';

type GroupedSettings = {
    branding: {
        site_name: string;
        tagline: string | null;
        logo_light: string | null;
        logo_dark: string | null;
        favicon: string | null;
        footer_text: string | null;
        show_footer: boolean;
        enable_passkey: boolean;
        enable_registration: boolean;
        enable_forgot_password: boolean;
    };
    maintenance: {
        mode: boolean;
        title: string;
        message: string | null;
        allowed_ips: string | null;
        bypass_users: string | null;
    };
    appearance: {
        theme: 'light' | 'dark' | 'system';
        accent_color: string;
        sidebar_style: 'light' | 'dark';
        navigation: 'sidebar' | 'top';
        sidebar_default: 'expanded' | 'collapsed';
        content_width: 'fixed' | 'full';
        density: 'compact' | 'comfortable' | 'spacious';
        font: 'Inter' | 'Roboto' | 'Open Sans' | 'Poppins';
        font_size: 'small' | 'medium' | 'large';
        table_rows: 10 | 25 | 50 | 100;
        table_sticky: boolean;
        table_zebra: boolean;
        table_dense: boolean;
        card_shadow: 'none' | 'small' | 'medium' | 'large';
        card_radius: number;
        card_flat: boolean;
        animation: boolean;
        animation_speed: 'fast' | 'normal' | 'slow';
        login_background: string | null;
        login_style: 'centered' | 'split' | 'glass' | 'minimal';
        login_background_type: 'image' | 'gradient' | 'solid';
        login_overlay: number;
        high_contrast: boolean;
        reduce_motion: boolean;
        large_text: boolean;
        custom_css: string;
    };
};

type SettingsTab = 'branding' | 'appearance' | 'security' | 'maintenance';

type Props = {
    settings: GroupedSettings;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Administration', href: organizationsRoute.index() },
            { title: 'Site Settings', href: settingsRoute.index() },
        ],
    },
});

const initialTab = (): SettingsTab => {
    if (typeof window === 'undefined') return 'branding';

    const tab = new URLSearchParams(window.location.search).get('tab');

    return ['branding', 'appearance', 'security', 'maintenance'].includes(
        tab ?? '',
    )
        ? (tab as SettingsTab)
        : 'branding';
};

const activeTab = ref<SettingsTab>(initialTab());
const page = usePage();
const canUpdate = computed(
    () => page.props.auth.permissions['settings.update'] === true,
);

const switchTab = (tab: SettingsTab): void => {
    if (
        hasUnsavedChanges.value &&
        !window.confirm('Discard unsaved changes?')
    ) {
        return;
    }

    activeTab.value = tab;

    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }
};

// ─── Branding Form ────────────────────────────────────────────────────────────

const brandingForm = useForm({
    site_name: props.settings.branding.site_name,
    tagline: props.settings.branding.tagline ?? '',
    logo_light: props.settings.branding.logo_light ?? '',
    logo_light_file: null as File | null,
    logo_dark: props.settings.branding.logo_dark ?? '',
    logo_dark_file: null as File | null,
    favicon: props.settings.branding.favicon ?? '',
    favicon_file: null as File | null,
    footer_text: props.settings.branding.footer_text ?? '',
    show_footer: props.settings.branding.show_footer !== false,
    enable_passkey: !!props.settings.branding.enable_passkey,
    enable_registration: !!props.settings.branding.enable_registration,
    enable_forgot_password: !!props.settings.branding.enable_forgot_password,
});

const submitBranding = (): void => {
    brandingForm.post(settingsBrandingRoute.update().url, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            brandingForm.logo_light_file = null;
            brandingForm.logo_dark_file = null;
            brandingForm.favicon_file = null;
            toast.success('Branding settings updated successfully.');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(first ?? 'Failed to save branding settings.');
        },
    });
};

const resetBranding = (): void => {
    brandingForm.reset();
    brandingForm.clearErrors();
};

// ─── Maintenance Form ─────────────────────────────────────────────────────────

const maintenanceForm = useForm({
    mode: !!props.settings.maintenance.mode,
    title: props.settings.maintenance.title ?? 'System Maintenance',
    message: props.settings.maintenance.message ?? '',
    allowed_ips: props.settings.maintenance.allowed_ips ?? '',
    bypass_users: props.settings.maintenance.bypass_users ?? '',
});

const submitMaintenance = (): void => {
    maintenanceForm.post(settingsMaintenanceRoute.update().url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Maintenance settings updated successfully.');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(first ?? 'Failed to save maintenance settings.');
        },
    });
};

const resetMaintenance = (): void => {
    maintenanceForm.reset();
    maintenanceForm.clearErrors();
};

// ─── Appearance Form ─────────────────────────────────────────────────────────

const appearanceForm = useForm({
    theme: props.settings.appearance.theme,
    accent_color: props.settings.appearance.accent_color,
    sidebar_style: props.settings.appearance.sidebar_style,
    navigation: props.settings.appearance.navigation,
    sidebar_default: props.settings.appearance.sidebar_default,
    content_width: props.settings.appearance.content_width,
    density: props.settings.appearance.density,
    font: props.settings.appearance.font,
    font_size: props.settings.appearance.font_size,
    table_rows: props.settings.appearance.table_rows,
    table_sticky: !!props.settings.appearance.table_sticky,
    table_zebra: !!props.settings.appearance.table_zebra,
    table_dense: !!props.settings.appearance.table_dense,
    card_shadow: props.settings.appearance.card_shadow,
    card_radius: props.settings.appearance.card_radius,
    card_flat: !!props.settings.appearance.card_flat,
    animation: !!props.settings.appearance.animation,
    animation_speed: props.settings.appearance.animation_speed,
    login_background: props.settings.appearance.login_background ?? '',
    login_background_file: null as File | null,
    login_style: props.settings.appearance.login_style,
    login_background_type: props.settings.appearance.login_background_type,
    login_overlay: props.settings.appearance.login_overlay,
    high_contrast: !!props.settings.appearance.high_contrast,
    reduce_motion: !!props.settings.appearance.reduce_motion,
    large_text: !!props.settings.appearance.large_text,
    custom_css: props.settings.appearance.custom_css ?? '',
});

const submitAppearance = (): void => {
    appearanceForm.post(settingsAppearanceRoute.update().url, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            applyAppearanceSettings({
                theme: appearanceForm.theme,
                accent_color: appearanceForm.accent_color,
                sidebar_style: appearanceForm.sidebar_style,
                navigation: appearanceForm.navigation,
                sidebar_default: appearanceForm.sidebar_default,
                content_width: appearanceForm.content_width,
                density: appearanceForm.density,
                font: appearanceForm.font,
                font_size: appearanceForm.font_size,
                table_rows: appearanceForm.table_rows,
                table_sticky: appearanceForm.table_sticky,
                table_zebra: appearanceForm.table_zebra,
                table_dense: appearanceForm.table_dense,
                card_shadow: appearanceForm.card_shadow,
                card_radius: appearanceForm.card_radius,
                card_flat: appearanceForm.card_flat,
                animation: appearanceForm.animation,
                animation_speed: appearanceForm.animation_speed,
                high_contrast: appearanceForm.high_contrast,
                reduce_motion: appearanceForm.reduce_motion,
                large_text: appearanceForm.large_text,
                custom_css: appearanceForm.custom_css,
            } satisfies AppearanceSettings);
            appearanceForm.login_background_file = null;
            toast.success('Appearance settings updated successfully.');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(first ?? 'Failed to save appearance settings.');
        },
    });
};

const resetAppearance = (): void => {
    appearanceForm.reset();
    appearanceForm.clearErrors();
};

const removeLoginBackground = (): void => {
    appearanceForm.login_background = '';
    appearanceForm.login_background_file = null;
};

const customCssLines = computed(() => {
    const lineCount = Math.max(8, appearanceForm.custom_css.split('\n').length);

    return Array.from({ length: lineCount }, (_, index) => index + 1);
});

const hasUnsavedChanges = computed(
    () =>
        brandingForm.isDirty ||
        maintenanceForm.isDirty ||
        appearanceForm.isDirty,
);

const warnBeforeUnload = (event: BeforeUnloadEvent): void => {
    if (!hasUnsavedChanges.value) return;

    event.preventDefault();
    event.returnValue = '';
};

onMounted(() => {
    window.addEventListener('beforeunload', warnBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', warnBeforeUnload);
});

// ─── Logo URL helper ─────────────────────────────────────────────────────────

const resolveLogoUrl = (path: string | null | undefined): string | null => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return `/storage/${path}`;
};
</script>

<template>
    <Head title="Site Settings" />

    <AdminPage
        title="Site Settings"
        description="Centralized, future-ready configuration for system branding, logos, and maintenance mode controls."
    >
        <div class="rounded-lg border bg-card">
            <!-- Navigation Tabs -->
            <div class="flex gap-1 border-b bg-muted/20 px-3 pt-3">
                <button
                    type="button"
                    :class="[
                        '-mb-px border-b-2 px-4 py-2 text-[13px] font-medium transition-colors focus:outline-none',
                        activeTab === 'branding'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                    ]"
                    @click="switchTab('branding')"
                >
                    Branding
                </button>
                <button
                    type="button"
                    :class="[
                        '-mb-px border-b-2 px-4 py-2 text-[13px] font-medium transition-colors focus:outline-none',
                        activeTab === 'appearance'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                    ]"
                    @click="switchTab('appearance')"
                >
                    Appearance
                </button>
                <button
                    type="button"
                    :class="[
                        '-mb-px border-b-2 px-4 py-2 text-[13px] font-medium transition-colors focus:outline-none',
                        activeTab === 'security'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                    ]"
                    @click="switchTab('security')"
                >
                    Security and Login
                </button>
                <button
                    type="button"
                    :class="[
                        '-mb-px border-b-2 px-4 py-2 text-[13px] font-medium transition-colors focus:outline-none',
                        activeTab === 'maintenance'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                    ]"
                    @click="switchTab('maintenance')"
                >
                    Maintenance
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="p-6">
                <!-- ─── BRANDING TAB ─── -->
                <div v-if="activeTab === 'branding'">
                    <form
                        class="max-w-2xl space-y-6"
                        @submit.prevent="submitBranding"
                    >
                        <fieldset :disabled="!canUpdate" class="space-y-6">
                            <div class="space-y-4">
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Identity
                                    </h3>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Configure the core public details of the
                                        application.
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div class="space-y-1.5">
                                        <Label class="text-[12px]"
                                            >Site Name
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Input
                                            v-model="brandingForm.site_name"
                                            class="h-8 text-[13px]"
                                            required
                                        />
                                        <InputError
                                            :message="
                                                brandingForm.errors.site_name
                                            "
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-[12px]"
                                            >Tagline</Label
                                        >
                                        <Input
                                            v-model="brandingForm.tagline"
                                            class="h-8 text-[13px]"
                                            placeholder="e.g. Next Gen Bulletin"
                                        />
                                        <InputError
                                            :message="
                                                brandingForm.errors.tagline
                                            "
                                        />
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-[12px]"
                                        >Footer Text</Label
                                    >
                                    <Input
                                        v-model="brandingForm.footer_text"
                                        class="h-8 text-[13px]"
                                        placeholder="e.g. © 2026 Enterprise Starter Kit, All rights reserved."
                                    />
                                    <InputError
                                        :message="
                                            brandingForm.errors.footer_text
                                        "
                                    />
                                </div>

                                <div
                                    class="flex items-center gap-3 rounded-md border bg-muted/30 p-3"
                                >
                                    <input
                                        id="show-footer"
                                        v-model="brandingForm.show_footer"
                                        type="checkbox"
                                        class="size-4 cursor-pointer rounded border-border accent-primary"
                                    />
                                    <div>
                                        <label
                                            for="show-footer"
                                            class="block cursor-pointer text-[13px] font-medium"
                                        >
                                            Show Site Footer
                                        </label>
                                        <span
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            Render the global copyright footer
                                            text at the bottom of the main
                                            dashboard layouts.
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Logo Section -->
                            <div class="space-y-4">
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Theme Logos &amp; Assets
                                    </h3>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Upload responsive visual branding assets
                                        and favicon indicators.
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <!-- Light Theme Logo -->
                                    <div class="space-y-2">
                                        <Label class="text-[12px] font-semibold"
                                            >Light Theme Logo</Label
                                        >
                                        <OrgLogoDropzone
                                            v-model="brandingForm.logo_light"
                                            v-model:file="
                                                brandingForm.logo_light_file
                                            "
                                        />
                                        <div
                                            v-if="brandingForm.logo_light"
                                            class="flex items-center gap-2 rounded-md border border-dashed bg-muted/40 p-2"
                                        >
                                            <span
                                                class="text-[10px] text-muted-foreground uppercase"
                                                >Current:</span
                                            >
                                            <img
                                                :src="
                                                    resolveLogoUrl(
                                                        brandingForm.logo_light,
                                                    )!
                                                "
                                                alt="Logo Light"
                                                class="h-6 rounded border bg-white object-contain px-1"
                                            />
                                        </div>
                                        <InputError
                                            :message="
                                                brandingForm.errors.logo_light
                                            "
                                        />
                                    </div>

                                    <!-- Dark Theme Logo -->
                                    <div class="space-y-2">
                                        <Label class="text-[12px] font-semibold"
                                            >Dark Theme Logo</Label
                                        >
                                        <OrgLogoDropzone
                                            v-model="brandingForm.logo_dark"
                                            v-model:file="
                                                brandingForm.logo_dark_file
                                            "
                                        />
                                        <div
                                            v-if="brandingForm.logo_dark"
                                            class="flex items-center gap-2 rounded-md border border-dashed bg-muted/40 p-2"
                                        >
                                            <span
                                                class="text-[10px] text-muted-foreground uppercase"
                                                >Current:</span
                                            >
                                            <img
                                                :src="
                                                    resolveLogoUrl(
                                                        brandingForm.logo_dark,
                                                    )!
                                                "
                                                alt="Logo Dark"
                                                class="h-6 rounded border border-slate-800 bg-slate-900 object-contain px-1"
                                            />
                                        </div>
                                        <InputError
                                            :message="
                                                brandingForm.errors.logo_dark
                                            "
                                        />
                                    </div>

                                    <!-- Favicon Upload -->
                                    <div
                                        class="col-span-1 space-y-2 md:col-span-2"
                                    >
                                        <Label class="text-[12px] font-semibold"
                                            >Favicon Icon (ICO, PNG, SVG)</Label
                                        >
                                        <OrgLogoDropzone
                                            v-model="brandingForm.favicon"
                                            v-model:file="
                                                brandingForm.favicon_file
                                            "
                                        />
                                        <div
                                            v-if="brandingForm.favicon"
                                            class="flex max-w-xs items-center gap-2 rounded-md border border-dashed bg-muted/40 p-2"
                                        >
                                            <span
                                                class="text-[10px] text-muted-foreground uppercase"
                                                >Current favicon:</span
                                            >
                                            <img
                                                :src="
                                                    resolveLogoUrl(
                                                        brandingForm.favicon,
                                                    )!
                                                "
                                                alt="Favicon"
                                                class="size-6 rounded border bg-white object-contain px-1"
                                            />
                                        </div>
                                        <InputError
                                            :message="
                                                brandingForm.errors.favicon
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="resetBranding"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="
                                    !canUpdate || brandingForm.processing
                                "
                            >
                                <Spinner
                                    v-if="brandingForm.processing"
                                    class="size-3.5"
                                />
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- ─── APPEARANCE TAB ─── -->
                <div v-if="activeTab === 'appearance'">
                    <form class="space-y-4" @submit.prevent="submitAppearance">
                        <fieldset :disabled="!canUpdate" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Palette
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Theme
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Configure the default color
                                                experience and navigation
                                                presentation.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Default Theme
                                            </Label>
                                            <select
                                                v-model="appearanceForm.theme"
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="light">
                                                    Light
                                                </option>
                                                <option value="dark">
                                                    Dark
                                                </option>
                                                <option value="system">
                                                    System
                                                </option>
                                            </select>
                                            <InputError
                                                :message="
                                                    appearanceForm.errors.theme
                                                "
                                            />
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Accent Color
                                            </Label>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <input
                                                    v-model="
                                                        appearanceForm.accent_color
                                                    "
                                                    type="color"
                                                    class="h-8 w-10 cursor-pointer rounded border bg-background p-1"
                                                />
                                                <Input
                                                    v-model="
                                                        appearanceForm.accent_color
                                                    "
                                                    class="h-8 font-mono text-[13px]"
                                                />
                                                <span
                                                    class="size-8 shrink-0 rounded-md border"
                                                    :style="{
                                                        backgroundColor:
                                                            appearanceForm.accent_color,
                                                    }"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                            <InputError
                                                :message="
                                                    appearanceForm.errors
                                                        .accent_color
                                                "
                                            />
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Sidebar Style
                                            </Label>
                                            <select
                                                v-model="
                                                    appearanceForm.sidebar_style
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="light">
                                                    Light
                                                </option>
                                                <option value="dark">
                                                    Dark
                                                </option>
                                            </select>
                                            <InputError
                                                :message="
                                                    appearanceForm.errors
                                                        .sidebar_style
                                                "
                                            />
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Navigation Style
                                            </Label>
                                            <select
                                                v-model="
                                                    appearanceForm.navigation
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="sidebar">
                                                    Sidebar
                                                </option>
                                                <option value="top">
                                                    Top Navigation
                                                </option>
                                            </select>
                                            <InputError
                                                :message="
                                                    appearanceForm.errors
                                                        .navigation
                                                "
                                            />
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <SlidersHorizontal
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Layout
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Tune workspace width, sidebar
                                                state, and interface density.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-3">
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Sidebar Default State
                                            </Label>
                                            <select
                                                v-model="
                                                    appearanceForm.sidebar_default
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="expanded">
                                                    Expanded
                                                </option>
                                                <option value="collapsed">
                                                    Collapsed
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Content Width
                                            </Label>
                                            <select
                                                v-model="
                                                    appearanceForm.content_width
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="fixed">
                                                    Fixed
                                                </option>
                                                <option value="full">
                                                    Full Width
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                UI Density
                                            </Label>
                                            <select
                                                v-model="appearanceForm.density"
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="compact">
                                                    Compact
                                                </option>
                                                <option value="comfortable">
                                                    Comfortable
                                                </option>
                                                <option value="spacious">
                                                    Spacious
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Type
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Typography
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Select the default application
                                                font and base text scale.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]"
                                                >Font Family</Label
                                            >
                                            <select
                                                v-model="appearanceForm.font"
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="Inter">
                                                    Inter
                                                </option>
                                                <option value="Roboto">
                                                    Roboto
                                                </option>
                                                <option value="Open Sans">
                                                    Open Sans
                                                </option>
                                                <option value="Poppins">
                                                    Poppins
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]"
                                                >Base Font Size</Label
                                            >
                                            <select
                                                v-model="
                                                    appearanceForm.font_size
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="small">
                                                    Small
                                                </option>
                                                <option value="medium">
                                                    Medium
                                                </option>
                                                <option value="large">
                                                    Large
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Table2
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Tables
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Configure default table paging
                                                and row presentation.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Default Rows Per Page
                                            </Label>
                                            <select
                                                v-model.number="
                                                    appearanceForm.table_rows
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option :value="10">10</option>
                                                <option :value="25">25</option>
                                                <option :value="50">50</option>
                                                <option :value="100">
                                                    100
                                                </option>
                                            </select>
                                        </div>
                                        <div class="grid gap-2">
                                            <label
                                                class="flex items-center gap-2 text-[13px]"
                                            >
                                                <input
                                                    v-model="
                                                        appearanceForm.table_sticky
                                                    "
                                                    type="checkbox"
                                                    class="size-4 rounded border-border accent-primary"
                                                />
                                                Sticky Header
                                            </label>
                                            <label
                                                class="flex items-center gap-2 text-[13px]"
                                            >
                                                <input
                                                    v-model="
                                                        appearanceForm.table_zebra
                                                    "
                                                    type="checkbox"
                                                    class="size-4 rounded border-border accent-primary"
                                                />
                                                Zebra Striping
                                            </label>
                                            <label
                                                class="flex items-center gap-2 text-[13px]"
                                            >
                                                <input
                                                    v-model="
                                                        appearanceForm.table_dense
                                                    "
                                                    type="checkbox"
                                                    class="size-4 rounded border-border accent-primary"
                                                />
                                                Dense Table Mode
                                            </label>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Building2
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Cards
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Define panel depth, radius, and
                                                flat card preferences.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]"
                                                >Card Shadow</Label
                                            >
                                            <select
                                                v-model="
                                                    appearanceForm.card_shadow
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="none">
                                                    None
                                                </option>
                                                <option value="small">
                                                    Small
                                                </option>
                                                <option value="medium">
                                                    Medium
                                                </option>
                                                <option value="large">
                                                    Large
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Card Radius:
                                                {{
                                                    appearanceForm.card_radius
                                                }}px
                                            </Label>
                                            <input
                                                v-model.number="
                                                    appearanceForm.card_radius
                                                "
                                                type="range"
                                                min="0"
                                                max="24"
                                                class="w-full accent-primary"
                                            />
                                        </div>
                                        <label
                                            class="flex items-center gap-2 text-[13px]"
                                        >
                                            <input
                                                v-model="
                                                    appearanceForm.card_flat
                                                "
                                                type="checkbox"
                                                class="size-4 rounded border-border accent-primary"
                                            />
                                            Flat Cards
                                        </label>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <WandSparkles
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Animation
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Control motion defaults and
                                                transition timing.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <label
                                            class="flex items-center gap-2 text-[13px]"
                                        >
                                            <input
                                                v-model="
                                                    appearanceForm.animation
                                                "
                                                type="checkbox"
                                                class="size-4 rounded border-border accent-primary"
                                            />
                                            Enable Animations
                                        </label>
                                        <div class="space-y-1.5">
                                            <Label class="text-[12px]">
                                                Transition Speed
                                            </Label>
                                            <select
                                                v-model="
                                                    appearanceForm.animation_speed
                                                "
                                                class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                            >
                                                <option value="fast">
                                                    Fast
                                                </option>
                                                <option value="normal">
                                                    Normal
                                                </option>
                                                <option value="slow">
                                                    Slow
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4 xl:col-span-2"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Image
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Login Page
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Configure login layout,
                                                background media, and overlay
                                                treatment.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]"
                                    >
                                        <div class="space-y-2">
                                            <Label class="text-[12px]">
                                                Login Background
                                            </Label>
                                            <OrgLogoDropzone
                                                v-model="
                                                    appearanceForm.login_background
                                                "
                                                v-model:file="
                                                    appearanceForm.login_background_file
                                                "
                                            />
                                            <div
                                                class="flex flex-wrap items-center justify-between gap-2 rounded-md border bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground"
                                            >
                                                <span
                                                    >Max upload size: 4 MB</span
                                                >
                                                <span
                                                    >Accepted: JPG, PNG,
                                                    WEBP</span
                                                >
                                            </div>
                                            <div
                                                v-if="
                                                    appearanceForm.login_background
                                                "
                                                class="flex items-center justify-between gap-3 rounded-md border border-dashed bg-muted/30 p-2"
                                            >
                                                <div
                                                    class="flex min-w-0 items-center gap-2"
                                                >
                                                    <img
                                                        :src="
                                                            resolveLogoUrl(
                                                                appearanceForm.login_background,
                                                            )!
                                                        "
                                                        alt="Login background"
                                                        class="h-10 w-16 rounded border object-cover"
                                                    />
                                                    <span
                                                        class="truncate text-[12px] text-muted-foreground"
                                                    >
                                                        Existing image will be
                                                        replaced when a new file
                                                        is uploaded.
                                                    </span>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="
                                                        removeLoginBackground
                                                    "
                                                >
                                                    Remove image
                                                </Button>
                                            </div>
                                            <InputError
                                                :message="
                                                    appearanceForm.errors
                                                        .login_background_file
                                                "
                                            />
                                        </div>

                                        <div class="grid gap-3">
                                            <div class="space-y-1.5">
                                                <Label class="text-[12px]"
                                                    >Login Style</Label
                                                >
                                                <select
                                                    v-model="
                                                        appearanceForm.login_style
                                                    "
                                                    class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                                >
                                                    <option value="centered">
                                                        Centered
                                                    </option>
                                                    <option value="split">
                                                        Split Screen
                                                    </option>
                                                    <option value="glass">
                                                        Glass
                                                    </option>
                                                    <option value="minimal">
                                                        Minimal
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="space-y-1.5">
                                                <Label class="text-[12px]"
                                                    >Background Type</Label
                                                >
                                                <select
                                                    v-model="
                                                        appearanceForm.login_background_type
                                                    "
                                                    class="h-8 w-full rounded-md border bg-background px-2 text-[13px] focus:ring-2 focus:ring-ring focus:outline-none"
                                                >
                                                    <option value="image">
                                                        Image
                                                    </option>
                                                    <option value="gradient">
                                                        Gradient
                                                    </option>
                                                    <option value="solid">
                                                        Solid Color
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="space-y-1.5">
                                                <Label class="text-[12px]">
                                                    Overlay Opacity:
                                                    {{
                                                        appearanceForm.login_overlay
                                                    }}%
                                                </Label>
                                                <input
                                                    v-model.number="
                                                        appearanceForm.login_overlay
                                                    "
                                                    type="range"
                                                    min="0"
                                                    max="100"
                                                    class="w-full accent-primary"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Check
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Accessibility
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Provide safer contrast and
                                                motion preferences.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-2">
                                        <label
                                            class="flex items-center gap-2 text-[13px]"
                                        >
                                            <input
                                                v-model="
                                                    appearanceForm.high_contrast
                                                "
                                                type="checkbox"
                                                class="size-4 rounded border-border accent-primary"
                                            />
                                            High Contrast
                                        </label>
                                        <label
                                            class="flex items-center gap-2 text-[13px]"
                                        >
                                            <input
                                                v-model="
                                                    appearanceForm.reduce_motion
                                                "
                                                type="checkbox"
                                                class="size-4 rounded border-border accent-primary"
                                            />
                                            Reduce Motion
                                        </label>
                                        <label
                                            class="flex items-center gap-2 text-[13px]"
                                        >
                                            <input
                                                v-model="
                                                    appearanceForm.large_text
                                                "
                                                type="checkbox"
                                                class="size-4 rounded border-border accent-primary"
                                            />
                                            Larger Text
                                        </label>
                                    </div>
                                </section>

                                <section
                                    class="rounded-md border bg-background p-4 xl:col-span-2"
                                >
                                    <div class="mb-3 flex items-start gap-2">
                                        <Code2
                                            class="mt-0.5 size-4 text-primary"
                                        />
                                        <div>
                                            <h3 class="text-sm font-semibold">
                                                Advanced
                                            </h3>
                                            <p
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                Add scoped custom CSS for
                                                system-wide appearance tweaks.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="overflow-hidden rounded-md border bg-muted/20"
                                    >
                                        <div
                                            class="grid grid-cols-[3rem_minmax(0,1fr)]"
                                        >
                                            <pre
                                                class="border-r bg-muted/50 py-2 text-right font-mono text-[12px] leading-5 text-muted-foreground select-none"
                                            ><span v-for="line in customCssLines" :key="line" class="block px-2">{{ line }}</span></pre>
                                            <textarea
                                                v-model="
                                                    appearanceForm.custom_css
                                                "
                                                rows="8"
                                                spellcheck="false"
                                                class="min-h-48 w-full resize-y border-0 bg-background px-3 py-2 font-mono text-[12px] leading-5 focus:ring-0 focus:outline-none"
                                                placeholder=":root { --app-accent: #2563eb; }"
                                            />
                                        </div>
                                    </div>
                                    <InputError
                                        :message="
                                            appearanceForm.errors.custom_css
                                        "
                                    />
                                </section>
                            </div>
                        </fieldset>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="resetAppearance"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="
                                    !canUpdate || appearanceForm.processing
                                "
                            >
                                <Spinner
                                    v-if="appearanceForm.processing"
                                    class="size-3.5"
                                />
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- ─── SECURITY AND LOGIN TAB ─── -->
                <div v-if="activeTab === 'security'">
                    <form
                        class="max-w-2xl space-y-6"
                        @submit.prevent="submitBranding"
                    >
                        <fieldset :disabled="!canUpdate" class="space-y-6">
                            <div>
                                <h3
                                    class="text-sm font-semibold text-foreground"
                                >
                                    Security &amp; Login
                                </h3>
                                <p class="text-[11px] text-muted-foreground">
                                    Configure advanced sign-in features and
                                    authentication flags.
                                </p>
                            </div>

                            <div
                                class="flex items-center gap-3 rounded-md border bg-muted/30 p-3"
                            >
                                <input
                                    id="enable-passkey"
                                    v-model="brandingForm.enable_passkey"
                                    type="checkbox"
                                    class="size-4 cursor-pointer rounded border-border accent-primary"
                                />
                                <div>
                                    <label
                                        for="enable-passkey"
                                        class="block cursor-pointer text-[13px] font-medium"
                                    >
                                        Enable Passkey Login
                                    </label>
                                    <span
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Allow users to register and sign in
                                        securely using biometric or device-based
                                        passkeys.
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-3 rounded-md border bg-muted/30 p-3"
                            >
                                <input
                                    id="enable-registration"
                                    v-model="brandingForm.enable_registration"
                                    type="checkbox"
                                    class="size-4 cursor-pointer rounded border-border accent-primary"
                                />
                                <div>
                                    <label
                                        for="enable-registration"
                                        class="block cursor-pointer text-[13px] font-medium"
                                    >
                                        Enable Registration
                                    </label>
                                    <span
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Allow new users to sign up and create
                                        accounts from the login page.
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-3 rounded-md border bg-muted/30 p-3"
                            >
                                <input
                                    id="enable-forgot-password"
                                    v-model="
                                        brandingForm.enable_forgot_password
                                    "
                                    type="checkbox"
                                    class="size-4 cursor-pointer rounded border-border accent-primary"
                                />
                                <div>
                                    <label
                                        for="enable-forgot-password"
                                        class="block cursor-pointer text-[13px] font-medium"
                                    >
                                        Enable Forgot Password
                                    </label>
                                    <span
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Display the password recovery link on
                                        the login page.
                                    </span>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="resetBranding"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="
                                    !canUpdate || brandingForm.processing
                                "
                            >
                                <Spinner
                                    v-if="brandingForm.processing"
                                    class="size-3.5"
                                />
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- ─── MAINTENANCE TAB ─── -->
                <div v-if="activeTab === 'maintenance'">
                    <form class="max-w-2xl space-y-6" @submit.prevent>
                        <fieldset :disabled="!canUpdate" class="space-y-6">
                            <div class="space-y-4">
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Maintenance Mode
                                    </h3>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Temporarily lock down client endpoints
                                        for system upgrades or maintenance
                                        periods.
                                    </p>
                                </div>

                                <!-- Alert Warning -->
                                <div
                                    class="flex gap-3 rounded-md border border-amber-200 bg-amber-50/50 p-4 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-400"
                                >
                                    <ShieldAlert
                                        class="size-5 shrink-0 text-amber-600 dark:text-amber-400"
                                    />
                                    <div class="space-y-1">
                                        <h4 class="text-[13px] font-semibold">
                                            Active Restrictive Mode Warning
                                        </h4>
                                        <p class="text-[11px] leading-relaxed">
                                            Enabling maintenance mode will
                                            restrict access to all normal user
                                            groups immediately. Active sessions
                                            will be redirected to the
                                            maintenance landing layout. Ensure
                                            bypass criteria (IPs/Users) are set
                                            correctly.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-3 rounded-md border bg-muted/30 p-3"
                                >
                                    <input
                                        id="maintenance-mode"
                                        v-model="maintenanceForm.mode"
                                        type="checkbox"
                                        class="size-4 cursor-pointer rounded border-border accent-primary"
                                    />
                                    <div>
                                        <label
                                            for="maintenance-mode"
                                            class="block cursor-pointer text-[13px] font-medium"
                                        >
                                            Enable Maintenance Mode
                                        </label>
                                        <span
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            Activate lock down state. The site
                                            displays the maintenance title and
                                            message to general traffic.
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div class="space-y-1.5">
                                        <Label class="text-[12px]"
                                            >Maintenance Title
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Input
                                            v-model="maintenanceForm.title"
                                            class="h-8 text-[13px]"
                                            required
                                        />
                                        <InputError
                                            :message="
                                                maintenanceForm.errors.title
                                            "
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label class="text-[12px]"
                                            >Message / Description</Label
                                        >
                                        <textarea
                                            v-model="maintenanceForm.message"
                                            rows="3"
                                            class="w-full rounded-md border bg-background px-3 py-1.5 text-[13px] focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                            placeholder="Brief maintenance description shown on the locked screen."
                                        />
                                        <InputError
                                            :message="
                                                maintenanceForm.errors.message
                                            "
                                        />
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Exception Rules Section -->
                            <div class="space-y-4">
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Exception / Bypass Rules
                                    </h3>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Define clients who are permitted to
                                        access the application normally during
                                        maintenance.
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div class="space-y-1.5">
                                        <Label class="text-[12px]"
                                            >Allowed IP Addresses</Label
                                        >
                                        <Input
                                            v-model="
                                                maintenanceForm.allowed_ips
                                            "
                                            class="h-8 text-[13px]"
                                            placeholder="e.g. 127.0.0.1, 192.168.1.100"
                                        />
                                        <span
                                            class="text-[10px] text-muted-foreground"
                                            >Comma-separated list of IPv4 or
                                            IPv6 client addresses.</span
                                        >
                                        <InputError
                                            :message="
                                                maintenanceForm.errors
                                                    .allowed_ips
                                            "
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label class="text-[12px]"
                                            >Bypass User IDs</Label
                                        >
                                        <Input
                                            v-model="
                                                maintenanceForm.bypass_users
                                            "
                                            class="h-8 text-[13px]"
                                            placeholder="e.g. 1, 25, 42"
                                        />
                                        <span
                                            class="text-[10px] text-muted-foreground"
                                            >Comma-separated list of User Model
                                            primary keys.</span
                                        >
                                        <InputError
                                            :message="
                                                maintenanceForm.errors
                                                    .bypass_users
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="resetMaintenance"
                            >
                                Cancel
                            </Button>

                            <!-- Confirmation wrapper for saving maintenance controls -->
                            <ConfirmAction
                                title="Apply Maintenance Settings?"
                                description="Are you sure you want to apply system maintenance settings? If maintenance mode is enabled, it takes effect instantly."
                                confirm-label="Apply settings"
                                @confirm="submitMaintenance"
                            >
                                <Button
                                    type="button"
                                    :disabled="
                                        !canUpdate || maintenanceForm.processing
                                    "
                                >
                                    <Spinner
                                        v-if="maintenanceForm.processing"
                                        class="size-3.5"
                                    />
                                    Save Changes
                                </Button>
                            </ConfirmAction>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminPage>
</template>
