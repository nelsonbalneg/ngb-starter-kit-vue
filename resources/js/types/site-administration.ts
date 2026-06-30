export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

export type Organization = {
    id: number;
    parent_id: number | null;
    type: 'university' | 'campus';
    name: string;
    slug: string;
    description: string | null;
    logo_path: string | null;
    address: string | null;
    is_active: boolean;
    users_count?: number;
    children?: Organization[];
    units?: OrganizationUnit[];
};

export type OrganizationUnit = {
    id: number;
    organization_id: number;
    parent_id: number | null;
    type: 'office' | 'department';
    name: string;
    logo_path: string | null;
    address: string | null;
    description: string | null;
    is_active: boolean;
    heads: Pick<AdminUser, 'id' | 'name' | 'email'>[];
    children?: OrganizationUnit[];
};

export type Role = {
    id: number;
    name: string;
    description: string | null;
    permissions_count?: number;
    users_count?: number;
};

export type Permission = {
    id: number;
    name: string;
    group: string;
    description: string | null;
};

export type AdminUser = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    is_active: boolean;
    locked_at: string | null;
    locked_reason: string | null;
    organizations: Organization[];
    roles?: Role[];
    permissions?: Permission[];
};

export type ModuleActivity = {
    id: number;
    module: string;
    action: string;
    description: string;
    causer: Pick<AdminUser, 'id' | 'name' | 'email'> | null;
    metadata: Record<string, unknown>;
    created_at: string | null;
    created_at_human: string | null;
    created_on: string | null;
    created_time: string | null;
};

export type ModuleActivityPage = {
    data: ModuleActivity[];
    next_cursor: string | null;
    per_page: number;
};

export type Lookup = {
    id: number;
    group: string;
    code: string;
    name: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
};

export type SiteSetting = {
    id: number;
    group: string;
    key: string;
    value: { value?: string | number | boolean | null } | null;
    type: string;
    description: string | null;
    is_public: boolean;
};

export type AppearanceSettings = {
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
    high_contrast: boolean;
    reduce_motion: boolean;
    large_text: boolean;
    custom_css: string;
};
