/**
 * 하단 네비게이션 컴포넌트
 *
 * 고객앱의 메인 네비게이션 바입니다.
 * - 5개의 탭 (HOME, ORDERS, QR, POINTS, ADMIN)
 * - 픽업/테이블 주문 모드 지원
 * - 장바구니 UI 포함
 * - 다국어 지원 (ko/es/en)
 */

import type React from 'react';
import { HomeIcon, OrdersIcon, QrCodeIcon, SaleIcon, SettingsIcon } from './icons';

/**
 * 네비게이션 아이템 정의
 */
interface NavItem {
    id: string;
    label: {
        ko: string;
        es: string;
        en: string;
    };
    icon: (active: boolean) => React.ReactElement;
}

/**
 * Props 인터페이스
 */
interface BottomNavigationProps {
    /** 현재 활성화된 탭 ID */
    activeTab: string;
    /** 탭 변경 핸들러 */
    onTabChange: (tabId: string) => void;
    /** 픽업 메뉴 (장바구니) 표시 여부 */
    showPickupMenu?: boolean;
    /** 장바구니 총액 */
    cartTotal?: string;
    /** 장바구니 아이템 개수 */
    cartItemCount?: number;
    /** 장바구니 클릭 핸들러 */
    onCartClick?: () => void;
    /** 모드 (픽업 또는 테이블 주문) */
    mode?: 'pickup' | 'table';
    /** 테이블 주문 시 주문 완료된 아이템 개수 */
    orderedItemCount?: number;
    /** 결제 페이지 이동 핸들러 */
    onPaymentClick?: () => void;
    /** 언어 설정 */
    language?: 'ko' | 'es' | 'en';
}

/**
 * 네비게이션 아이템 정의
 */
const NAV_ITEMS: NavItem[] = [
    {
        id: 'home',
        label: { ko: '홈', es: 'Inicio', en: 'Home' },
        icon: (active) => <HomeIcon active={active} />,
    },
    {
        id: 'orders',
        label: { ko: '주문', es: 'Pedidos', en: 'Orders' },
        icon: (active) => <OrdersIcon active={active} />,
    },
    {
        id: 'qr',
        label: { ko: 'QR', es: 'QR', en: 'QR' },
        icon: (active) => <QrCodeIcon active={active} />,
    },
    {
        id: 'points',
        label: { ko: '포인트', es: 'Puntos', en: 'Points' },
        icon: (active) => <SaleIcon active={active} />,
    },
    {
        id: 'admin',
        label: { ko: '관리', es: 'Admin', en: 'Admin' },
        icon: (active) => <SettingsIcon active={active} />,
    },
];

/**
 * 다국어 번역
 */
const TRANSLATIONS = {
    ko: {
        cart: '장바구니',
        pay: '결제하기',
    },
    es: {
        cart: 'Carrito',
        pay: 'Pagar',
    },
    en: {
        cart: 'Cart',
        pay: 'Pay',
    },
};

/**
 * 하단 네비게이션 컴포넌트
 */
export function BottomNavigation({
    activeTab,
    onTabChange,
    showPickupMenu = false,
    cartTotal = '$650',
    cartItemCount = 3,
    onCartClick,
    mode = 'pickup',
    orderedItemCount = 0,
    onPaymentClick,
    language = 'ko',
}: BottomNavigationProps) {
    const t = TRANSLATIONS[language];

    // 장바구니 UI 렌더링
    if (showPickupMenu) {
        // 테이블 주문 모드 비활성화 조건
        const isDisabled = mode === 'table' ? orderedItemCount === 0 && cartItemCount === 0 : cartItemCount === 0;

        const handleButtonClick = () => {
            if (isDisabled) return;

            if (mode === 'table') {
                // 테이블 주문 모드
                if (cartItemCount > 0) {
                    onCartClick?.();
                } else if (orderedItemCount > 0 && onPaymentClick) {
                    onPaymentClick();
                }
            } else {
                // 픽업 모드
                onCartClick?.();
            }
        };

        return (
            <nav className="fixed bottom-0 left-0 right-0 bg-[#434343] z-50">
                <div className="flex flex-row items-center relative size-full">
                    <div className="box-border content-stretch flex items-center justify-between px-[clamp(16px,2.86vw,20px)] py-0 relative w-full h-[clamp(50px,8.57vw,60px)] min-h-[clamp(50px,8.57vw,60px)]">
                        {/* 장바구니 총액 */}
                        <p
                            className={`font-['Noto_Sans:Medium',_sans-serif] font-medium leading-[normal] relative shrink-0 text-[clamp(18px,3.43vw,24px)] text-nowrap tracking-[-0.48px] whitespace-pre transition-all duration-200 ${
                                isDisabled ? 'text-[#878787] opacity-40' : 'text-[#03d67b]'
                            }`}
                            style={{ fontVariationSettings: "'CTGR' 0, 'wdth' 100" }}
                        >
                            {cartTotal}
                        </p>

                        {/* 장바구니/결제 버튼 */}
                        <button
                            onClick={handleButtonClick}
                            disabled={isDisabled}
                            className={`flex flex-col font-['Noto_Sans:Medium',_'Noto_Sans_KR:Medium',_sans-serif] font-medium justify-center leading-[0] relative shrink-0 text-[clamp(18px,3.43vw,24px)] text-center text-nowrap tracking-[-0.48px] transition-all duration-200 ${
                                isDisabled
                                    ? 'bg-[#878787] text-[#434343] cursor-not-allowed opacity-40'
                                    : 'bg-[#03d67b] text-[#434343] hover:bg-[#00b96f] cursor-pointer'
                            } rounded-[4px] px-[clamp(14px,2.86vw,20px)] py-[clamp(8px,1.43vw,10px)]`}
                            style={{ fontVariationSettings: "'CTGR' 0, 'wdth' 100" }}
                        >
                            <span className="break-words overflow-hidden">
                                {mode === 'table' && cartItemCount === 0 && orderedItemCount > 0 ? t.pay : t.cart}
                                {cartItemCount > 0 && ` (${cartItemCount})`}
                            </span>
                        </button>
                    </div>
                </div>
            </nav>
        );
    }

    // 일반 네비게이션 UI 렌더링
    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-white dark:bg-[#434343] border-t border-gray-200 dark:border-gray-700 z-50">
            <div className="flex items-center justify-around h-16 px-2">
                {NAV_ITEMS.map((item) => {
                    const isActive = activeTab === item.id;

                    return (
                        <button
                            key={item.id}
                            onClick={() => onTabChange(item.id)}
                            className="flex flex-col items-center justify-center flex-1 gap-1 py-2 transition-colors"
                        >
                            {/* 아이콘 */}
                            <div className="flex items-center justify-center h-6">{item.icon(isActive)}</div>

                            {/* 라벨 */}
                            <span
                                className={`text-xs font-medium ${
                                    isActive ? 'text-[#00B96F]' : 'text-[#878787] dark:text-[#A0A5A3]'
                                }`}
                            >
                                {item.label[language]}
                            </span>
                        </button>
                    );
                })}
            </div>
        </nav>
    );
}

export default BottomNavigation;
