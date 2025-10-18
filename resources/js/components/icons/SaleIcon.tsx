import IconBase from './IconBase';
import { svgPaths } from './svgPaths';

interface SaleIconProps {
    active: boolean;
}

/**
 * 세일/포인트 아이콘
 *
 * 할인 또는 포인트를 나타내는 퍼센트(%) 아이콘입니다.
 */
export default function SaleIcon({ active }: SaleIconProps) {
    return (
        <IconBase active={active}>
            <g>
                {/* 배경 기어 모양 */}
                <path d={svgPaths.sale.gear} fill="currentColor" />

                {/* 퍼센트 선 */}
                <path d={svgPaths.sale.percentLine} stroke="currentColor" strokeLinecap="round" strokeWidth="1.30971" />

                {/* 위쪽 원 (%) */}
                <path d={svgPaths.sale.topCircle.outer} fill="currentColor" />
                <path d={svgPaths.sale.topCircle.segments} fill="currentColor" />

                {/* 아래쪽 원 (%) */}
                <path d={svgPaths.sale.bottomCircle.outer} fill="currentColor" />
                <path d={svgPaths.sale.bottomCircle.segments} fill="currentColor" />
            </g>
        </IconBase>
    );
}
