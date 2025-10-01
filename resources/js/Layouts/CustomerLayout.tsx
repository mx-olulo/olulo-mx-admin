import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';

/**
 * CustomerLayout Props
 */
interface Props extends PropsWithChildren {
    title?: string;
    showHeader?: boolean;
}

/**
 * 고객앱 공통 레이아웃
 *
 * 모든 고객 페이지에서 사용되는 기본 레이아웃입니다.
 * - 공통 헤더 (로고, 타이틀)
 * - children 렌더링
 * - TailwindCSS + daisyUI 스타일링
 * - 라이트/다크 모드 대응
 * - Phase 3: 기본 구조만 구현
 */
export default function CustomerLayout({
    children,
    title = 'Olulo MX',
    showHeader = true,
}: Props) {
    return (
        <div className="min-h-screen bg-base-100">
            {/* 헤더 */}
            {showHeader && (
                <header className="navbar bg-primary text-primary-content shadow-lg">
                    <div className="container mx-auto">
                        <div className="flex-1">
                            <Link
                                href="/"
                                className="btn btn-ghost normal-case text-xl"
                                style={{ fontFamily: 'Noto Sans' }}
                            >
                                <span className="text-2xl mr-2">🍽️</span>
                                {title}
                            </Link>
                        </div>
                        <div className="flex-none">
                            {/* 추후 메뉴/장바구니 아이콘 추가 */}
                        </div>
                    </div>
                </header>
            )}

            {/* 메인 컨텐츠 */}
            <main className="min-h-[calc(100vh-64px)]">
                {children}
            </main>

            {/* 푸터 (선택적) */}
            {/* 추후 필요시 추가 */}
        </div>
    );
}
