import { PropsWithChildren } from 'react';
import { BottomNavigation } from '@/components/BottomNavigation';
import { Header } from '@/components/Header';
import { LoginHeader, Language } from '@/components/LoginHeader';

/**
 * CustomerLayout Props
 */
interface Props extends PropsWithChildren {
    /** 페이지 타이틀 */
    title?: string;
    /** 헤더 타입: 'default' | 'login' | 'none' */
    headerType?: 'default' | 'login' | 'none';
    /** 뒤로가기 버튼 표시 여부 */
    showBack?: boolean;
    /** 위치 아이콘 표시 여부 */
    showLocation?: boolean;
    /** 하단 네비게이션 표시 여부 */
    showBottomNav?: boolean;
    /** 현재 활성화된 탭 ID */
    activeTab?: string;
    /** 뒤로가기 버튼 클릭 핸들러 */
    onBack?: () => void;
    /** 로그인 버튼 클릭 핸들러 (LoginHeader용) */
    onLoginClick?: () => void;
    /** 언어 설정 (LoginHeader용) */
    language?: Language;
}


/**
 * 고객앱 공통 레이아웃
 *
 * 모든 고객 페이지에서 사용되는 기본 레이아웃입니다.
 * - 공통 헤더 (Primary 색상 배경, 뒤로가기 버튼, 타이틀)
 * - children 렌더링
 * - 하단 네비게이션 (5개 탭)
 * - TailwindCSS 스타일링
 * - 라이트/다크 모드 대응
 * - Viewport 기반 반응형 디자인
 * - Phase 3: 기본 구조 + 임시 이모지 아이콘
 */
export default function CustomerLayout({
    children,
    title = 'Olulo MX',
    headerType = 'default',
    showBack = false,
    showLocation = false,
    showBottomNav = true,
    activeTab = 'home',
    onBack,
    onLoginClick,
    language = 'ko',
}: Props) {
    const handleTabChange = (tabId: string) => {
        // 탭 변경 시 추가 로직 (필요시)
        console.log('Tab changed:', tabId);
    };

    const handleBackClick = () => {
        if (onBack) {
            onBack();
        } else {
            // 기본 뒤로가기 동작
            window.history.back();
        }
    };

    const handleLoginClick = () => {
        if (onLoginClick) {
            onLoginClick();
        } else {
            // 기본 로그인 페이지로 이동
            window.location.href = '/customer/auth/login';
        }
    };

    return (
        <div className="min-h-screen bg-base-200">
            {/* 헤더 */}
            {headerType === 'default' && (
                <Header
                    title={title}
                    showBack={showBack}
                    showLocation={showLocation}
                    onBack={handleBackClick}
                />
            )}
            {headerType === 'login' && (
                <LoginHeader
                    onLoginClick={handleLoginClick}
                    language={language}
                />
            )}

            {/* 메인 컨텐츠 - 하단 네비게이션 공간 확보 */}
            <main className={showBottomNav ? 'pb-20' : ''}>
                {children}
            </main>

            {/* 하단 네비게이션 */}
            {showBottomNav && (
                <BottomNavigation
                    activeTab={activeTab}
                    onTabChange={handleTabChange}
                />
            )}
        </div>
    );
}
