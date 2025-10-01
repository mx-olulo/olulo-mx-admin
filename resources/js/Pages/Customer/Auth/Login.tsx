import { Link } from '@inertiajs/react';
import CustomerLayout from '@/Layouts/CustomerLayout';

/**
 * 고객 로그인 페이지
 *
 * Firebase 인증을 통한 로그인 페이지입니다.
 * - FirebaseUI 컨테이너 제공
 * - ID Token → Sanctum 세션 확립 플로우 준비
 * - 하단 네비게이션 숨김 (인증 전 페이지)
 * - Phase 3: Placeholder UI만 구현 (실제 Firebase 초기화는 추후)
 */
export default function Login() {
    return (
        <CustomerLayout title="로그인" showBack={true} showBottomNav={false}>
            {/* 로그인 카드 */}
            <div className="container mx-auto px-4 py-12">
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden max-w-md mx-auto">
                    <div className="p-8">
                        {/* 로고 영역 */}
                        <div className="text-center mb-6">
                            <div className="text-6xl mb-4">🔐</div>
                            <h2 className="text-3xl font-bold" style={{ fontFamily: 'Noto Sans' }}>
                                로그인
                            </h2>
                            <p className="text-sm opacity-70 mt-2">
                                Firebase 인증으로 안전하게 로그인하세요
                            </p>
                        </div>

                        {/* Placeholder 알림 */}
                        <div className="p-4 rounded-xl flex items-center gap-3 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800 mb-6">
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
                            <span>Phase 3: Firebase UI 준비 중</span>
                        </div>

                        {/* FirebaseUI 컨테이너 */}
                        <div
                            id="firebaseui-auth-container"
                            className="min-h-[200px] border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900"
                        >
                            <div className="text-center">
                                <div className="text-4xl mb-4">🔥</div>
                                <p className="font-semibold mb-2">Firebase 로그인 UI</p>
                                <p className="text-sm opacity-70">실제 초기화는 Phase 4에서 구현됩니다</p>
                            </div>

                            {/* 예상 로그인 방법 표시 */}
                            <div className="mt-6 w-full space-y-3">
                                <div className="inline-flex items-center justify-start w-full px-4 py-2 rounded-xl font-medium transition-colors border-2 border-gray-300 hover:border-[#03D67B] hover:bg-[#03D67B] hover:text-white" disabled>
                                    <svg className="w-5 h-5" viewBox="0 0 24 24">
                                        <path
                                            fill="currentColor"
                                            d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"
                                        />
                                    </svg>
                                    Google 로그인
                                </div>
                                <div className="inline-flex items-center justify-start w-full px-4 py-2 rounded-xl font-medium transition-colors border-2 border-gray-300 hover:border-[#03D67B] hover:bg-[#03D67B] hover:text-white" disabled>
                                    <svg className="w-5 h-5" viewBox="0 0 24 24">
                                        <path
                                            fill="currentColor"
                                            d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"
                                        />
                                    </svg>
                                    Facebook 로그인
                                </div>
                                <div className="inline-flex items-center justify-start w-full px-4 py-2 rounded-xl font-medium transition-colors border-2 border-gray-300 hover:border-[#03D67B] hover:bg-[#03D67B] hover:text-white" disabled>
                                    <svg className="w-5 h-5" viewBox="0 0 24 24">
                                        <path
                                            fill="currentColor"
                                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"
                                        />
                                    </svg>
                                    WhatsApp 로그인
                                </div>
                            </div>
                        </div>

                        {/* 추가 정보 */}
                        <div className="border-t border-gray-200 dark:border-gray-700 my-4 my-6"></div>
                        <div className="text-center text-sm">
                            <p className="opacity-70">
                                계정이 없으신가요?
                            </p>
                            <p className="opacity-70 mt-1">
                                로그인 시 자동으로 회원가입됩니다
                            </p>
                        </div>

                        {/* 뒤로가기 버튼 */}
                        <Link
                            href="/"
                            className="inline-flex items-center justify-center w-full px-4 py-2 rounded-2xl font-medium transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 mt-4"
                            style={{ fontFamily: 'Noto Sans' }}
                        >
                            ← 홈으로 돌아가기
                        </Link>
                    </div>
                </div>

                {/* 보안 정보 */}
                <div className="text-center mt-8 text-sm opacity-60">
                    <p>🔒 Firebase로 안전하게 보호되는 인증</p>
                    <p className="mt-1">개인정보는 암호화되어 안전하게 저장됩니다</p>
                </div>
            </div>
        </CustomerLayout>
    );
}
