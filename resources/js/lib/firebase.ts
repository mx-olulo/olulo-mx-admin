/**
 * Firebase 설정 및 초기화
 *
 * Firebase Authentication을 위한 설정 파일입니다.
 * - 환경변수에서 Firebase 설정 로드
 * - Firebase App 초기화
 * - Firebase Auth 인스턴스 제공
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

import { initializeApp, FirebaseApp } from 'firebase/app';
import { getAuth, Auth, connectAuthEmulator } from 'firebase/auth';

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
 */
export const app: FirebaseApp = initializeApp(firebaseConfig);

/**
 * Firebase Auth 인스턴스
 */
export const auth: Auth = getAuth(app);

/**
 * Firebase Emulator 연결
 *
 * 개발 환경(local)에서 Firebase Emulator를 자동으로 사용합니다.
 * 어드민 화면과 동일한 로직: import.meta.env.DEV 기반 판단
 */
if (import.meta.env.DEV) {
    const emulatorHost = import.meta.env.VITE_FIREBASE_AUTH_EMULATOR_HOST || 'localhost:9099';

    // Emulator 연결 (한 번만 호출)
    try {
        connectAuthEmulator(auth, `http://${emulatorHost}`, { disableWarnings: true });
        console.log(`🔧 Firebase Auth Emulator connected: ${emulatorHost}`);
    } catch (_error) {
        // 이미 연결된 경우 에러 무시
        console.warn('Auth Emulator already connected');
    }
}

/**
 * Firebase 초기화 상태 확인
 *
 * @returns Firebase가 정상적으로 초기화되었는지 여부
 */
export function isFirebaseInitialized(): boolean {
    return app !== null && auth !== null;
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

    return requiredFields.every((field) => {
        const value = firebaseConfig[field as keyof typeof firebaseConfig];
        return value !== undefined && value !== '';
    });
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
