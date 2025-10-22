// @CODE:STORE-LIST-001:DATA | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

/**
 * Organization 타입
 */
export interface Organization {
    id: number;
    name: string;
    description?: string;
    contact_email?: string;
    contact_phone?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

/**
 * Store 타입
 */
export interface Store {
    id: number;
    organization_id: number;
    name: string;
    description?: string;
    address?: string;
    phone?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    organization: Organization | null;
}

/**
 * Paginated Response 타입
 */
export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
}

/**
 * Store 목록 페이지 Props
 */
export interface StoreListPageProps {
    stores: PaginatedData<Store>;
}
