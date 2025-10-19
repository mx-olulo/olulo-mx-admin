// @CODE:STORE-LIST-001:UI | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md | TEST: resources/js/Components/Customer/__tests__/StoreCard.test.tsx

import React from 'react';
import type { Store } from '@/types';

/**
 * StoreCard Props
 */
interface StoreCardProps {
    store: Store;
    onClick?: () => void;
}

/**
 * StoreCard 컴포넌트
 *
 * Store 정보를 카드 형태로 표시
 * - Organization Badge (상단, primary 색상)
 * - Store Name (제목, 1줄 제한)
 * - Description (본문, 2줄 제한 + ellipsis)
 * - Active Status (우측 상단, 초록색 dot)
 *
 * ref/CompanyListPage.tsx 디자인 패턴 차용
 */
export default function StoreCard({ store, onClick }: StoreCardProps) {
    return (
        <div
            className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow cursor-pointer"
            onClick={onClick}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    onClick?.();
                }
            }}
        >
            <div className="p-6">
                {/* Header: Organization Badge + Active Dot */}
                <div className="flex items-start justify-between mb-3">
                    {/* Organization Badge */}
                    <span className="inline-block px-3 py-1 text-xs font-medium rounded-full bg-[#03D67B] text-white">
                        {store.organization.name}
                    </span>

                    {/* Active Status Dot */}
                    {store.is_active && (
                        <span
                            className="inline-block w-3 h-3 rounded-full bg-green-500"
                            title="Active"
                            aria-label="Active store"
                        />
                    )}
                </div>

                {/* Store Name (1줄 제한) */}
                <h3 className="text-lg font-bold text-gray-900 dark:text-white truncate mb-2" title={store.name}>
                    {store.name}
                </h3>

                {/* Description (2줄 제한 + ellipsis) */}
                {store.description && (
                    <p
                        className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2"
                        title={store.description}
                    >
                        {store.description}
                    </p>
                )}

                {/* Address (optional) */}
                {store.address && (
                    <p className="text-xs text-gray-500 dark:text-gray-500 mt-2 truncate" title={store.address}>
                        {store.address}
                    </p>
                )}
            </div>
        </div>
    );
}
