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
    organizations: Organization[];
    roles?: Role[];
    permissions?: Permission[];
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
