import { Link, router } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';

/**
 * 사용자 인터페이스
 */
interface User {
    id: number;
    name: string;
    email: string;
}

/**
 * Orders 페이지 Props
 */
interface Props {
    user: User | null;
}

/**
 * 마이페이지 - 주문 내역
 *
 * 고객의 주문 내역을 표시하는 페이지입니다.
 * - 사용자 정보 표시
 * - 보호 API 호출 테스트 버튼
 * - 로그아웃 버튼
 * - 하단 네비게이션 표시 (activeTab="orders")
 * - Phase 3: Placeholder UI만 구현
 */
export default function Orders({ user }: Props) {
    /**
     * 로그아웃 핸들러
     */
    const handleLogout = () => {
        if (confirm('로그아웃하시겠습니까?')) {
            router.post('/customer/auth/logout');
        }
    };

    /**
     * API 테스트 핸들러
     */
    const handleTestProtectedApi = () => {
        alert('보호 API 호출 테스트 (Phase 4에서 구현)');
    };

    return (
        <CustomerLayout title="내 주문" showBack={true} activeTab="orders" showBottomNav={true}>
            {/* 메인 컨텐츠 */}
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-4xl mx-auto space-y-6">
                    {/* 사용자 정보 카드 */}
                    {user ? (
                        <>
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-4">
                                            <div className="relative">
                                                <div className="flex items-center justify-center bg-[#03D67B] text-white rounded-full w-16 h-16">
                                                    <span className="text-2xl">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <h2 className="text-2xl font-bold" style={{ fontFamily: 'Noto Sans' }}>
                                                    {user.name}
                                                </h2>
                                                <p className="text-sm opacity-70">{user.email}</p>
                                                <div className="px-2 py-1 text-xs rounded-full font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mt-2 inline-block">인증됨</div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={handleLogout}
                                            className="inline-flex items-center justify-center px-3 py-1.5 text-sm rounded-xl font-medium transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                className="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                                />
                                            </svg>
                                            로그아웃
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Placeholder 알림 */}
                            <div className="p-4 rounded-xl flex items-center gap-3 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800 shadow-lg">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    className="stroke-current shrink-0 h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                <span>Phase 3: Placeholder 페이지입니다</span>
                            </div>

                            {/* 주문 내역 카드 */}
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                                <div className="p-6">
                                    <h3 className="text-xl font-bold mb-4" style={{ fontFamily: 'Noto Sans' }}>
                                        📦 내 주문 내역
                                    </h3>

                                    {/* 빈 상태 */}
                                    <div className="text-center py-12">
                                        <div className="text-6xl mb-4">🛒</div>
                                        <p className="text-lg font-semibold mb-2">주문 내역이 없습니다</p>
                                        <p className="text-sm opacity-70">첫 주문을 시작해보세요!</p>
                                    </div>

                                    {/* 더미 주문 데이터 예시 */}
                                    <div className="border-t border-gray-200 dark:border-gray-700 my-4">향후 표시될 주문 예시</div>
                                    <div className="space-y-3 opacity-50">
                                        <div className="border border-gray-300 dark:border-gray-700 rounded-xl p-4">
                                            <div className="flex justify-between items-start mb-2">
                                                <div>
                                                    <p className="font-bold">주문 #12345</p>
                                                    <p className="text-sm opacity-70">2024-01-15 14:30</p>
                                                </div>
                                                <div className="px-2 py-1 text-xs rounded-full font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">완료</div>
                                            </div>
                                            <p className="text-sm">타코 x2, 부리또 x1</p>
                                            <p className="text-sm font-bold mt-2">$450 MXN</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* 테스트 버튼 */}
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                                <div className="p-6">
                                    <h3 className="text-xl font-bold mb-4" style={{ fontFamily: 'Noto Sans' }}>
                                        🧪 개발자 테스트
                                    </h3>
                                    <button
                                        type="button"
                                        onClick={handleTestProtectedApi}
                                        className="inline-flex items-center justify-center w-full px-4 py-2 rounded-2xl font-medium transition-colors border-2 border-gray-300 hover:border-[#03D67B] hover:bg-[#03D67B] hover:text-white"
                                        style={{ fontFamily: 'Noto Sans' }}
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        보호 API 호출 테스트
                                    </button>
                                    <p className="text-xs text-center opacity-70 mt-2">
                                        Phase 4에서 실제 API 연동이 구현됩니다
                                    </p>
                                </div>
                            </div>
                        </>
                    ) : (
                        /* 로그인 필요 상태 */
                        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                            <div className="p-8 flex flex-col items-center text-center">
                                <div className="text-6xl mb-4">🔒</div>
                                <h2 className="text-2xl font-bold mb-4" style={{ fontFamily: 'Noto Sans' }}>
                                    로그인이 필요합니다
                                </h2>
                                <p className="mb-6 opacity-70">
                                    주문 내역을 확인하려면 로그인해주세요
                                </p>
                                <Link
                                    href="/customer/auth/login"
                                    className="inline-flex items-center justify-center px-6 py-3 text-lg rounded-2xl font-medium transition-colors bg-[#03D67B] text-white hover:bg-[#00B96F] active:bg-[#009959]"
                                    style={{ fontFamily: 'Noto Sans' }}
                                >
                                    로그인하기
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </CustomerLayout>
    );
}
