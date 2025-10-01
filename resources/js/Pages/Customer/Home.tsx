import { Link } from '@inertiajs/react';

/**
 * 고객 홈 페이지
 *
 * 첫 진입 페이지입니다.
 * - 로그인 또는 비회원 진행 선택
 * - Phase 3: Placeholder UI만 구현
 * - TODO: QR 코드 처리는 별도 진행에서 구현
 */
export default function Home() {
    return (
        <div className="min-h-screen bg-gradient-to-b from-base-200 to-base-300">
            {/* 헤더 영역 */}
            <div className="bg-primary text-primary-content">
                <div className="container mx-auto px-4 py-6">
                    <h1 className="text-2xl font-bold" style={{ fontFamily: 'Noto Sans' }}>
                        Olulo MX
                    </h1>
                    <p className="text-sm opacity-90 mt-1">
                        음식 배달 서비스
                    </p>
                </div>
            </div>

            {/* 메인 컨텐츠 */}
            <div className="container mx-auto px-4 py-8">
                {/* 환영 카드 */}
                <div className="card bg-base-100 shadow-2xl max-w-2xl mx-auto rounded-2xl">
                    <div className="card-body p-6">
                        <h2 className="card-title text-2xl mb-4" style={{ fontFamily: 'Noto Sans' }}>
                            환영합니다! 👋
                        </h2>

                        {/* Placeholder 알림 */}
                        <div className="alert alert-warning rounded-xl mb-6">
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

                        {/* 액션 버튼들 */}
                        <div className="flex flex-col gap-4 mt-6">
                            {/* 로그인 버튼 */}
                            <Link
                                href="/customer/auth/login"
                                className="btn btn-primary btn-lg rounded-2xl text-white shadow-lg"
                                style={{ fontFamily: 'Noto Sans' }}
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    className="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                                    />
                                </svg>
                                로그인
                            </Link>

                            {/* 비회원 계속 버튼 */}
                            <button
                                type="button"
                                className="btn btn-outline btn-lg rounded-2xl"
                                style={{ fontFamily: 'Noto Sans' }}
                                disabled
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    className="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                                    />
                                </svg>
                                비회원으로 계속
                                <span className="badge badge-ghost">추후 구현</span>
                            </button>
                        </div>

                        {/* 푸터 정보 */}
                        <div className="divider mt-8"></div>
                        <div className="text-center text-sm opacity-60">
                            <p>QR 코드를 스캔하여 주문을 시작하세요</p>
                            <p className="mt-1">멕시코 음식 배달 플랫폼</p>
                        </div>
                    </div>
                </div>

                {/* 추가 정보 카드 */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8 max-w-2xl mx-auto">
                    <div className="card bg-base-100 shadow-lg rounded-2xl">
                        <div className="card-body items-center text-center p-4">
                            <div className="text-4xl mb-2">🍔</div>
                            <h3 className="font-bold">다양한 메뉴</h3>
                            <p className="text-xs opacity-70">맛있는 음식 선택</p>
                        </div>
                    </div>
                    <div className="card bg-base-100 shadow-lg rounded-2xl">
                        <div className="card-body items-center text-center p-4">
                            <div className="text-4xl mb-2">⚡</div>
                            <h3 className="font-bold">빠른 배달</h3>
                            <p className="text-xs opacity-70">신속한 서비스</p>
                        </div>
                    </div>
                    <div className="card bg-base-100 shadow-lg rounded-2xl">
                        <div className="card-body items-center text-center p-4">
                            <div className="text-4xl mb-2">💳</div>
                            <h3 className="font-bold">간편 결제</h3>
                            <p className="text-xs opacity-70">안전한 결제</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
