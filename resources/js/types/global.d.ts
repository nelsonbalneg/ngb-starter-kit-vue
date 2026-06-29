import type { Auth } from '@/types/auth';

type Branding = {
    site_name?: string;
    tagline?: string;
    logo_light?: string | null;
    logo_dark?: string | null;
    favicon?: string | null;
    footer_text?: string;
    show_footer?: boolean;
    enable_passkey?: boolean;
    enable_registration?: boolean;
    enable_forgot_password?: boolean;
};

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            branding: Branding;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
