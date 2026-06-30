import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type {
    Appearance,
    AppearanceSettings,
    ResolvedAppearance,
} from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const defaultAppearanceSettings: AppearanceSettings = {
    theme: 'system',
    accent_color: '#2563eb',
    sidebar_style: 'light',
    navigation: 'sidebar',
    sidebar_default: 'expanded',
    content_width: 'fixed',
    density: 'comfortable',
    font: 'Inter',
    font_size: 'medium',
    table_rows: 25,
    table_sticky: true,
    table_zebra: false,
    table_dense: false,
    card_shadow: 'small',
    card_radius: 8,
    card_flat: false,
    animation: true,
    animation_speed: 'normal',
    high_contrast: false,
    reduce_motion: false,
    large_text: false,
    custom_css: '',
};

const appearance = ref<Appearance>('system');
const globalAppearance = ref<AppearanceSettings>(defaultAppearanceSettings);

const managedClasses = [
    'theme-light',
    'theme-dark',
    'theme-system',
    'density-compact',
    'density-comfortable',
    'density-spacious',
    'sidebar-light',
    'sidebar-dark',
    'navigation-sidebar',
    'navigation-top',
    'sidebar-default-expanded',
    'sidebar-default-collapsed',
    'content-fixed',
    'content-full',
    'font-size-small',
    'font-size-medium',
    'font-size-large',
    'table-dense',
    'table-sticky',
    'table-zebra',
    'card-shadow-none',
    'card-shadow-small',
    'card-shadow-medium',
    'card-shadow-large',
    'card-flat',
    'animations-enabled',
    'animations-disabled',
    'animation-fast',
    'animation-normal',
    'animation-slow',
    'high-contrast',
    'reduce-motion',
    'large-text',
];

const fontStacks: Record<AppearanceSettings['font'], string> = {
    Inter: 'Inter, ui-sans-serif, system-ui, sans-serif',
    Roboto: 'Roboto, Inter, ui-sans-serif, system-ui, sans-serif',
    'Open Sans': "'Open Sans', Inter, ui-sans-serif, system-ui, sans-serif",
    Poppins: 'Poppins, Inter, ui-sans-serif, system-ui, sans-serif',
};

const normalizeAppearanceSettings = (
    settings?: Partial<AppearanceSettings> | null,
): AppearanceSettings => ({
    ...defaultAppearanceSettings,
    ...(settings ?? {}),
});

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') return;

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') return null;

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const prefersDark = (): boolean => mediaQuery()?.matches ?? false;

const resolveTheme = (value: Appearance): ResolvedAppearance =>
    value === 'system' ? (prefersDark() ? 'dark' : 'light') : value;

export function updateTheme(value: Appearance): void {
    if (typeof document === 'undefined') return;

    const resolved = resolveTheme(value);

    document.documentElement.classList.toggle('dark', resolved === 'dark');
}

const sanitizeCustomCss = (css: string): string =>
    css
        .replace(/@import\s+[^;]+;/gi, '')
        .replace(/expression\s*\([^)]*\)/gi, '')
        .replace(/javascript\s*:/gi, '')
        .replace(/<\/?style[^>]*>/gi, '')
        .trim();

const setCustomCss = (css: string): void => {
    if (typeof document === 'undefined') return;

    const styleId = 'site-appearance-custom-css';
    let style = document.getElementById(styleId) as HTMLStyleElement | null;

    if (!style) {
        style = document.createElement('style');
        style.id = styleId;
        document.head.appendChild(style);
    }

    style.textContent = sanitizeCustomCss(css);
};

const hexToRgb = (hex: string): { r: number; g: number; b: number } | null => {
    const match = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

    if (!match) return null;

    return {
        r: parseInt(match[1], 16),
        g: parseInt(match[2], 16),
        b: parseInt(match[3], 16),
    };
};

const readableForeground = (hex: string): string => {
    const rgb = hexToRgb(hex);

    if (!rgb) return '#ffffff';

    const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;

    return luminance > 0.62 ? '#111827' : '#ffffff';
};

export function applyAppearanceSettings(
    settings?: Partial<AppearanceSettings> | null,
): AppearanceSettings {
    const normalized = normalizeAppearanceSettings(settings);

    globalAppearance.value = normalized;
    appearance.value = normalized.theme;

    if (typeof document === 'undefined') {
        return normalized;
    }

    const root = document.documentElement;
    const body = document.body;
    const targets = [root, body];

    targets.forEach((target) => target.classList.remove(...managedClasses));
    targets.forEach((target) =>
        target.classList.add(
            `theme-${normalized.theme}`,
            `density-${normalized.density}`,
            `sidebar-${normalized.sidebar_style}`,
            `navigation-${normalized.navigation}`,
            `sidebar-default-${normalized.sidebar_default}`,
            `content-${normalized.content_width}`,
            `font-size-${normalized.font_size}`,
            `card-shadow-${normalized.card_shadow}`,
            normalized.animation ? 'animations-enabled' : 'animations-disabled',
            `animation-${normalized.animation_speed}`,
        ),
    );

    targets.forEach((target) => {
        target.classList.toggle('table-dense', normalized.table_dense);
        target.classList.toggle('table-sticky', normalized.table_sticky);
        target.classList.toggle('table-zebra', normalized.table_zebra);
        target.classList.toggle('card-flat', normalized.card_flat);
        target.classList.toggle('high-contrast', normalized.high_contrast);
        target.classList.toggle('reduce-motion', normalized.reduce_motion);
        target.classList.toggle('large-text', normalized.large_text);
    });

    root.style.setProperty('--app-accent-color', normalized.accent_color);
    root.style.setProperty('--primary', normalized.accent_color);
    root.style.setProperty('--ring', normalized.accent_color);
    root.style.setProperty(
        '--primary-foreground',
        readableForeground(normalized.accent_color),
    );
    root.style.setProperty('--app-card-radius', `${normalized.card_radius}px`);
    root.style.setProperty('--radius', `${normalized.card_radius}px`);
    root.style.setProperty('--app-font-family', fontStacks[normalized.font]);
    root.style.setProperty('--font-sans', fontStacks[normalized.font]);
    root.style.setProperty(
        '--app-transition-speed',
        normalized.animation_speed === 'fast'
            ? '120ms'
            : normalized.animation_speed === 'slow'
              ? '300ms'
              : '200ms',
    );
    root.style.setProperty(
        '--app-card-shadow',
        normalized.card_shadow === 'none'
            ? 'none'
            : normalized.card_shadow === 'large'
              ? '0 10px 24px rgb(15 23 42 / 0.14)'
              : normalized.card_shadow === 'medium'
                ? '0 6px 16px rgb(15 23 42 / 0.12)'
                : '0 1px 3px rgb(15 23 42 / 0.10)',
    );

    updateTheme(normalized.theme);
    setCookie('appearance', normalized.theme);
    setCookie(
        'sidebar_state',
        normalized.sidebar_default === 'expanded' ? 'true' : 'false',
    );
    setCustomCss(normalized.custom_css);

    return normalized;
}

const handleSystemThemeChange = (): void => {
    updateTheme(globalAppearance.value.theme);
};

export function initializeTheme(): void {
    applyAppearanceSettings(defaultAppearanceSettings);
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useGlobalAppearance(): {
    appearanceSettings: Ref<AppearanceSettings>;
    applyAppearanceSettings: typeof applyAppearanceSettings;
} {
    const page = usePage();

    onMounted(() => {
        applyAppearanceSettings(
            page.props.appearance as Partial<AppearanceSettings> | undefined,
        );
    });

    watch(
        () => page.props.appearance,
        (settings) => {
            applyAppearanceSettings(settings as Partial<AppearanceSettings>);
        },
        { deep: true },
    );

    return {
        appearanceSettings: globalAppearance,
        applyAppearanceSettings,
    };
}

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        appearance.value = globalAppearance.value.theme;
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        resolveTheme(appearance.value),
    );

    function updateAppearance(value: Appearance): void {
        applyAppearanceSettings({
            ...globalAppearance.value,
            theme: value,
        });
        localStorage.setItem('appearance', value);
        setCookie('appearance', value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
