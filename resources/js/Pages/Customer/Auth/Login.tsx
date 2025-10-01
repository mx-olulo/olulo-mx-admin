import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import CustomerLayout from '@/Layouts/CustomerLayout';
import { auth, validateFirebaseConfig } from '@/lib/firebase';
import * as firebaseui from 'firebaseui';
import { EmailAuthProvider, GoogleAuthProvider } from 'firebase/auth';
import 'firebaseui/dist/firebaseui.css';

/**
 * 고객 로그인 페이지
 *
 * Firebase 인증을 통한 로그인 페이지입니다.
 * - FirebaseUI를 통한 실제 인증 구현
 * - Google 및 Email/Password 로그인 지원
 * - ID Token → Sanctum 세션 확립 플로우
 * - 하단 네비게이션 숨김 (인증 전 페이지)
 */
export default function Login() {
    // FirebaseUI 인스턴스 상태
    const [uiInstance, setUiInstance] = useState<firebaseui.auth.AuthUI | null>(null);
    // 에러 상태
    const [error, setError] = useState<string | null>(null);
    // Firebase 설정 유효성
    const [isConfigValid, setIsConfigValid] = useState<boolean>(true);

    useEffect(() => {
        // Firebase 설정 유효성 확인
        const configValid = validateFirebaseConfig();
        setIsConfigValid(configValid);

        if (!configValid) {
            setError('Firebase 설정이 올바르지 않습니다. 환경변수를 확인해주세요.');
            return;
        }

        try {
            // FirebaseUI 인스턴스 생성 또는 가져오기
            let ui = firebaseui.auth.AuthUI.getInstance();
            if (!ui) {
                ui = new firebaseui.auth.AuthUI(auth);
            }
            setUiInstance(ui);

            // FirebaseUI 설정
            const uiConfig: firebaseui.auth.Config = {
                signInOptions: [
                    // Google 로그인
                    GoogleAuthProvider.PROVIDER_ID,
                    // Email/Password 로그인
                    EmailAuthProvider.PROVIDER_ID,
                ],
                callbacks: {
                    /**
                     * 로그인 성공 콜백
                     *
                     * Firebase 인증 성공 후 ID Token을 획득하여
                     * Laravel API로 전송하여 Sanctum 세션을 확립합니다.
                     */
                    signInSuccessWithAuthResult: (authResult) => {
                        // ID Token 획득
                        authResult.user.getIdToken().then((idToken) => {
                            // CSRF 토큰 가져오기
                            const csrfToken = document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '';

                            // Laravel API로 토큰 전송하여 세션 확립
                            fetch('/api/customer/auth/firebase/login', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ idToken }),
                                credentials: 'include', // 쿠키 포함 (Sanctum 세션)
                            })
                                .then((response) => {
                                    if (response.ok) {
                                        // 세션 확립 성공 - 내 주문 페이지로 이동
                                        window.location.href = '/my/orders';
                                    } else {
                                        // 세션 확립 실패
                                        setError('로그인에 실패했습니다. 다시 시도해주세요.');
                                        console.error('Session establishment failed:', response);
                                    }
                                })
                                .catch((error) => {
                                    // 네트워크 에러
                                    setError('네트워크 오류가 발생했습니다. 다시 시도해주세요.');
                                    console.error('Network error:', error);
                                });
                        }).catch((error) => {
                            // ID Token 획득 실패
                            setError('인증 토큰 획득에 실패했습니다. 다시 시도해주세요.');
                            console.error('Failed to get ID token:', error);
                        });

                        // 자동 리다이렉트 방지 (수동으로 처리)
                        return false;
                    },
                    /**
                     * UI 표시 전 콜백
                     */
                    uiShown: () => {
                        // 로딩 상태 제거 (필요시)
                        console.log('FirebaseUI rendered');
                    },
                },
                // 추가 UI 설정
                signInFlow: 'redirect', // 리다이렉트 방식 로그인
                tosUrl: '/terms', // 이용약관 URL (추후 구현)
                privacyPolicyUrl: '/privacy', // 개인정보처리방침 URL (추후 구현)
            };

            // FirebaseUI 시작
            ui.start('#firebaseui-auth-container', uiConfig);

        } catch (err) {
            // FirebaseUI 초기화 실패
            setError('Firebase 인증 초기화에 실패했습니다.');
            console.error('FirebaseUI initialization error:', err);
        }

        // Cleanup: 컴포넌트 언마운트 시 FirebaseUI 정리
        return () => {
            if (uiInstance) {
                // FirebaseUI 인스턴스 삭제
                uiInstance.delete();
            }
        };
    }, []); // 빈 배열: 컴포넌트 마운트 시 한 번만 실행

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

                        {/* 에러 메시지 표시 */}
                        {error && (
                            <div className="p-4 rounded-xl flex items-center gap-3 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 mb-6">
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
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>{error}</span>
                            </div>
                        )}

                        {/* Firebase 설정 누락 경고 */}
                        {!isConfigValid && (
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
                                <div>
                                    <p className="font-semibold">Firebase 환경변수 누락</p>
                                    <p className="text-sm mt-1">
                                        .env 파일에 VITE_FIREBASE_* 환경변수를 설정해주세요.
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* FirebaseUI 컨테이너 */}
                        <div
                            id="firebaseui-auth-container"
                            className="min-h-[200px]"
                        ></div>

                        {/* 추가 정보 */}
                        <div className="border-t border-gray-200 dark:border-gray-700 my-6"></div>
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
