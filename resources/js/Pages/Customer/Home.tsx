// @CODE:STORE-LIST-001:UI | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

import React, { useState, useMemo } from 'react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import StoreCard from '@/Components/Customer/StoreCard';
import SearchBar from '@/Components/Customer/SearchBar';
import type { StoreListPageProps } from '@/types';

/**
 * 고객 홈 페이지
 *
 * SPEC-STORE-LIST-001: Store 목록 페이지
 * - 활성 Store만 표시
 * - Organization 정보 포함
 * - 검색 필터 (클라이언트 사이드)
 * - 다국어 지원 (ko/es/en)
 */
export default function Home({ stores }: StoreListPageProps) {
    const [searchQuery, setSearchQuery] = useState('');

    // TODO: laravel-react-i18n으로 교체 예정
    const t = (key: string) => {
        const translations: Record<string, string> = {
            'customer.home.title': 'Store List',
            'customer.home.search_placeholder': 'Search store...',
            'customer.home.no_results': 'No results found',
            'customer.home.no_stores': 'No stores registered',
            'customer.home.loading': 'Loading...',
        };
        return translations[key] || key;
    };

    // 클라이언트 사이드 검색 필터
    const filteredStores = useMemo(() => {
        if (!searchQuery.trim()) {
            return stores.data;
        }

        const query = searchQuery.toLowerCase();
        return stores.data.filter((store) => store.name.toLowerCase().includes(query));
    }, [stores.data, searchQuery]);

    return (
        <CustomerLayout title={t('customer.home.title')} activeTab="home" showBottomNav={true}>
            {/* 메인 컨텐츠 */}
            <div className="container mx-auto px-4 py-8">
                {/* Search Bar */}
                <SearchBar
                    value={searchQuery}
                    onChange={setSearchQuery}
                    placeholder={t('customer.home.search_placeholder')}
                />

                {/* Store Grid */}
                {filteredStores.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredStores.map((store) => (
                            <StoreCard
                                key={store.id}
                                store={store}
                                onClick={() => {
                                    // TODO: Navigate to store detail page
                                    console.log('Store clicked:', store.id);
                                }}
                            />
                        ))}
                    </div>
                ) : (
                    /* Empty State */
                    <div className="flex flex-col items-center justify-center py-16">
                        <svg
                            className="w-24 h-24 text-gray-300 dark:text-gray-600 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={1.5}
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                        <p className="text-lg font-medium text-gray-500 dark:text-gray-400">
                            {searchQuery ? t('customer.home.no_results') : t('customer.home.no_stores')}
                        </p>
                    </div>
                )}

                {/* Pagination */}
                {stores.last_page > 1 && (
                    <div className="flex justify-center mt-8">
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                            Page {stores.current_page} of {stores.last_page} ({stores.total} stores)
                        </div>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}
