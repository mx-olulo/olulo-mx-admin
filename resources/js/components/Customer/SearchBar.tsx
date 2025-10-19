// @CODE:STORE-LIST-001:UI | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md | TEST: resources/js/Components/Customer/__tests__/SearchBar.test.tsx

import React from 'react';

/**
 * SearchBar Props
 */
interface SearchBarProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}

/**
 * SearchBar 컴포넌트
 *
 * 검색 입력 필드
 * - Input 필드
 * - onChange 핸들러
 * - I18N placeholder
 */
export default function SearchBar({ value, onChange, placeholder }: SearchBarProps) {
    return (
        <div className="w-full max-w-2xl mx-auto mb-6">
            <div className="relative">
                {/* Search Icon */}
                <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg
                        className="w-5 h-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>

                {/* Search Input */}
                <input
                    type="text"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    className="w-full pl-12 pr-4 py-3 text-base rounded-2xl border-2 border-gray-200 focus:border-[#03D67B] focus:outline-none transition-colors bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    aria-label="Search stores"
                />
            </div>
        </div>
    );
}
