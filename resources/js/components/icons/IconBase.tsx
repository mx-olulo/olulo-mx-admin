/**
 * 공통 아이콘 베이스 컴포넌트
 *
 * 모든 네비게이션 아이콘의 기본 구조를 제공합니다.
 * active 상태에 따라 색상이 자동으로 변경됩니다.
 */

interface IconBaseProps {
    /** 아이콘 활성화 상태 */
    active: boolean;
    /** SVG viewBox 속성 */
    viewBox?: string;
    /** 아이콘 너비 (px) */
    width?: number;
    /** 아이콘 높이 (px) */
    height?: number;
    /** SVG 자식 요소 */
    children: React.ReactNode;
    /** 추가 클래스명 */
    className?: string;
}

export default function IconBase({
    active,
    viewBox = '0 0 22 22',
    width = 22,
    height = 22,
    children,
    className = '',
}: IconBaseProps) {
    return (
        <div
            className={`relative shrink-0 ${className}`}
            style={{ width: `${width}px`, height: `${height}px` }}
        >
            <svg className="block size-full" fill="none" preserveAspectRatio="none" viewBox={viewBox}>
                {children}
            </svg>
        </div>
    );
}

/**
 * 활성/비활성 색상 반환
 */
export function getIconColor(active: boolean): string {
    return active ? '#00B96F' : '#878787';
}
