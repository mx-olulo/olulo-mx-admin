import IconBase from './IconBase';
import { getIconColor } from './get-icon-color';
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
    const color = getIconColor(active);

    return (
        <IconBase active={active}>
            <g>
                {/* 배경 기어 모양 */}
                <path d={svgPaths.sale.gear} fill={color} />

                {/* 퍼센트 선 */}
                <path d={svgPaths.sale.percentLine} stroke={color} strokeLinecap="round" strokeWidth="1.30971" />

                {/* 위쪽 원 (%) */}
                <path d={svgPaths.sale.topCircle.outer} fill={color} />
                <path d={svgPaths.sale.topCircle.segments} fill={color} />

                {/* 아래쪽 원 (%) */}
                <path d={svgPaths.sale.bottomCircle.outer} fill={color} />
                <path d={svgPaths.sale.bottomCircle.segments} fill={color} />
            </g>
        </IconBase>
    );
}
