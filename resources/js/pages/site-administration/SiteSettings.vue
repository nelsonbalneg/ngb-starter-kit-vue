<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { computed, ref } from 'vue';
import { Building2, Landmark, Laptop, ShieldAlert } from '@lucide/vue';
import AdminPage from '@/components/site-administration/AdminPage.vue';
import ConfirmAction from '@/components/site-administration/ConfirmAction.vue';
import InputError from '@/components/InputError.vue';
import OrgLogoDropzone from '@/components/site-administration/OrgLogoDropzone.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import organizationsRoute from '@/routes/site-administration/organizations';
import settingsRoute from '@/routes/site-administration/settings';

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
};

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

const activeTab = ref<'branding' | 'security' | 'maintenance'>('branding');
const page = usePage();
const canUpdate = computed(
    () => page.props.auth.permissions['settings.update'] === true,
);

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
    brandingForm.post('/site-administration/settings/branding', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            brandingForm.logo_light_file = null;
            brandingForm.logo_dark_file = null;
            brandingForm.favicon_file = null;
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
    maintenanceForm.post('/site-administration/settings/maintenance', {
        preserveScroll: true,
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
                    @click="activeTab = 'branding'"
                >
                    Branding
                </button>
                <button
                    type="button"
                    :class="[
                        '-mb-px border-b-2 px-4 py-2 text-[13px] font-medium transition-colors focus:outline-none',
                        activeTab === 'security'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                    ]"
                    @click="activeTab = 'security'"
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
                    @click="activeTab = 'maintenance'"
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
