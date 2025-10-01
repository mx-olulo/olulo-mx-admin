import { PropsWithChildren } from 'react';
import { router } from '@inertiajs/react';

/**
 * CustomerLayout Props
 */
interface Props extends PropsWithChildren {
    /** 페이지 타이틀 */
    title?: string;
    /** 헤더 표시 여부 */
    showHeader?: boolean;
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
}

/**
 * Header 컴포넌트
 *
 * Primary 색상 배경의 상단 헤더
 * - 뒤로가기 버튼 (선택적)
 * - 타이틀 좌측 배치
 * - 위치 아이콘 (선택적)
 * - Viewport 기반 반응형 디자인
 */
function Header({
    title,
    showBack = false,
    showLocation = false,
    onBack
}: Pick<Props, 'title' | 'showBack' | 'showLocation' | 'onBack'>) {
    return (
        <header className="bg-[#03D67B] dark:bg-[#00B96F] sticky top-0 z-50 w-full">
            <div className="flex items-center justify-between p-[min(5vw,1rem)] h-[calc(3.5rem+1vw)]">
                <div className="flex items-center gap-[min(3vw,1rem)]">
                    {showBack && (
                        <button
                            onClick={onBack}
                            className="text-white hover:bg-white/20 p-2 rounded-lg transition-colors flex-shrink-0"
                            aria-label="뒤로가기"
                        >
                            {/* ArrowLeft 아이콘 (임시 이모지) */}
                            <span className="text-xl">←</span>
                        </button>
                    )}
                    <h1
                        className="text-white font-bold text-[calc(1rem+0.5vw)] tracking-tight truncate"
                        style={{ fontFamily: 'Noto Sans' }}
                    >
                        {title}
                    </h1>
                </div>
                {showLocation && (
                    <button
                        className="text-white hover:bg-white/20 p-2 rounded-lg transition-colors flex-shrink-0"
                        aria-label="위치 설정"
                    >
                        {/* MapPin 아이콘 (임시 이모지) */}
                        <span className="text-xl">📍</span>
                    </button>
                )}
            </div>
        </header>
    );
}

/**
 * BottomNavigation 컴포넌트
 *
 * 하단 고정 네비게이션 바
 * - 5개 탭: HOME, ORDERS, QR CODE, POINTS, ADMIN
 * - Primary 색상 활성화 표시
 * - 라이트/다크 모드 지원
 * - Phase 3: 임시 이모지 아이콘 사용
 */
function BottomNavigation({
    activeTab = 'home',
    onTabChange
}: {
    activeTab?: string;
    onTabChange: (tabId: string) => void;
}) {
    const navItems = [
        { id: 'home', label: 'HOME', icon: '🏠', href: '/' },
        { id: 'orders', label: 'ORDERS', icon: '📋', href: '/orders' },
        { id: 'qr', label: 'QR CODE', icon: '📱', href: '/qr' },
        { id: 'points', label: 'POINTS', icon: '💰', href: '/points' },
        { id: 'admin', label: 'ADMIN', icon: '⚙️', href: '/admin' },
    ];

    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-[#F6F6F6] dark:bg-[#434343] border-t border-[#A0A5A3]/20 dark:border-[#878787]/30 z-50">
            <div className="flex items-center justify-around p-[min(2.5vw,0.75rem)] max-w-md mx-auto">
                {navItems.map((item) => {
                    const isActive = activeTab === item.id;
                    return (
                        <button
                            key={item.id}
                            onClick={() => {
                                onTabChange(item.id);
                                router.visit(item.href);
                            }}
                            className="flex flex-col items-center gap-[min(1vw,0.25rem)] p-[min(1.5vw,0.5rem)] rounded-lg transition-all hover:bg-white/50 dark:hover:bg-[#202020]/50 active:scale-95 min-w-0"
                            aria-label={item.label}
                        >
                            <div className={`text-2xl ${
                                isActive
                                    ? 'text-[#00B96F]'
                                    : 'text-[#878787] dark:text-[#A0A5A3]'
                            }`}>
                                {item.icon}
                            </div>
                            <span
                                className={`text-[calc(0.625rem+0.125vw)] font-medium leading-none text-center ${
                                    isActive
                                        ? 'text-[#00B96F]'
                                        : 'text-[#878787] dark:text-[#A0A5A3]'
                                }`}
                                style={{ fontFamily: 'Noto Sans' }}
                            >
                                {item.label}
                            </span>
                        </button>
                    );
                })}
            </div>
        </nav>
    );
}

/**
 * 고객앱 공통 레이아웃
 *
 * 모든 고객 페이지에서 사용되는 기본 레이아웃입니다.
 * - 공통 헤더 (Primary 색상 배경, 뒤로가기 버튼, 타이틀)
 * - children 렌더링
 * - 하단 네비게이션 (5개 탭)
 * - TailwindCSS + daisyUI 스타일링
 * - 라이트/다크 모드 대응
 * - Viewport 기반 반응형 디자인
 * - Phase 3: 기본 구조 + 임시 이모지 아이콘
 */
export default function CustomerLayout({
    children,
    title = 'Olulo MX',
    showHeader = true,
    showBack = false,
    showLocation = false,
    showBottomNav = true,
    activeTab = 'home',
    onBack,
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

    return (
        <div className="min-h-screen bg-base-200">
            {/* 헤더 */}
            {showHeader && (
                <Header
                    title={title}
                    showBack={showBack}
                    showLocation={showLocation}
                    onBack={handleBackClick}
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
