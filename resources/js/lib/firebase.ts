/**
 * Firebase 설정 및 초기화
 *
 * Firebase Authentication을 위한 설정 파일입니다.
 * - 환경변수에서 Firebase 설정 로드
 * - Firebase App 초기화
 * - Firebase Auth 인스턴스 제공
 * - Phase 3: 설정 준비만 완료 (실제 초기화는 주석 처리)
 *
 * 환경변수 필요 항목 (.env):
 * - VITE_FIREBASE_API_KEY
 * - VITE_FIREBASE_AUTH_DOMAIN
 * - VITE_FIREBASE_PROJECT_ID
 * - VITE_FIREBASE_STORAGE_BUCKET
 * - VITE_FIREBASE_MESSAGING_SENDER_ID
 * - VITE_FIREBASE_APP_ID
 * - VITE_FIREBASE_MEASUREMENT_ID (optional)
 */

// Firebase SDK imports (Phase 4에서 활성화)
// import { initializeApp, FirebaseApp } from 'firebase/app';
// import { getAuth, Auth } from 'firebase/auth';

/**
 * Firebase 설정 객체
 *
 * 환경변수에서 Firebase 프로젝트 설정을 로드합니다.
 */
export const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

/**
 * Firebase App 인스턴스
 *
 * Phase 4에서 활성화:
 * export const app: FirebaseApp = initializeApp(firebaseConfig);
 */
// export const app: FirebaseApp | null = null;

/**
 * Firebase Auth 인스턴스
 *
 * Phase 4에서 활성화:
 * export const auth: Auth = getAuth(app);
 */
// export const auth: Auth | null = null;

/**
 * Firebase 초기화 상태 확인
 *
 * @returns Firebase가 정상적으로 초기화되었는지 여부
 */
export function isFirebaseInitialized(): boolean {
    // Phase 3: 항상 false 반환 (아직 초기화 안됨)
    return false;

    // Phase 4에서 활성화:
    // return app !== null && auth !== null;
}

/**
 * Firebase 설정 유효성 검증
 *
 * 필수 환경변수가 모두 설정되어 있는지 확인합니다.
 *
 * @returns 설정이 유효한지 여부
 */
export function validateFirebaseConfig(): boolean {
    const requiredFields = [
        'apiKey',
        'authDomain',
        'projectId',
        'storageBucket',
        'messagingSenderId',
        'appId',
    ];

    return requiredFields.every(
        (field) => firebaseConfig[field as keyof typeof firebaseConfig] !== undefined
    );
}

/**
 * Firebase 설정 정보 출력 (디버깅용)
 *
 * 민감 정보는 마스킹하여 출력합니다.
 */
export function logFirebaseConfig(): void {
    if (import.meta.env.DEV) {
        console.log('Firebase Config:', {
            apiKey: firebaseConfig.apiKey ? '***' + firebaseConfig.apiKey.slice(-4) : 'Not set',
            authDomain: firebaseConfig.authDomain || 'Not set',
            projectId: firebaseConfig.projectId || 'Not set',
            storageBucket: firebaseConfig.storageBucket || 'Not set',
            messagingSenderId: firebaseConfig.messagingSenderId ? '***' : 'Not set',
            appId: firebaseConfig.appId ? '***' + firebaseConfig.appId.slice(-4) : 'Not set',
            measurementId: firebaseConfig.measurementId || 'Not set',
            isValid: validateFirebaseConfig(),
            isInitialized: isFirebaseInitialized(),
        });
    }
}

/**
 * Phase 4 구현 예정 항목
 *
 * TODO: Firebase SDK 설치
 * - npm install firebase
 *
 * TODO: Firebase 초기화 활성화
 * - initializeApp() 호출
 * - getAuth() 호출
 *
 * TODO: FirebaseUI 설정
 * - FirebaseUI 라이브러리 설치 및 초기화
 * - 로그인 프로바이더 설정 (Google, Facebook, WhatsApp 등)
 *
 * TODO: ID Token 검증 및 세션 확립
 * - Firebase ID Token 획득
 * - Laravel API로 토큰 전송 (/customer/auth/firebase/callback)
 * - Sanctum 세션 쿠키 수신
 *
 * TODO: 로그아웃 처리
 * - Firebase signOut()
 * - Laravel 세션 종료
 */
