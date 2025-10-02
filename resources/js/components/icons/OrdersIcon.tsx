import IconBase, { getIconColor } from './IconBase';
import { svgPaths } from './svgPaths';

interface OrdersIconProps {
    active: boolean;
}

/**
 * 주문 아이콘
 *
 * 주문 목록을 나타내는 문서 모양 아이콘입니다.
 */
export default function OrdersIcon({ active }: OrdersIconProps) {
    const color = getIconColor(active);

    return (
        <IconBase active={active} width={18.34} height={22} viewBox="0 0 19 22">
            <g clipPath="url(#clip0_6_3504)">
                <path d={svgPaths.orders.outline} fill={color} />
                {svgPaths.orders.lines.map((line, index) => (
                    <path
                        key={index}
                        d={line.d}
                        stroke={color}
                        strokeLinecap="round"
                        strokeMiterlimit="10"
                        strokeWidth="1.02583"
                    />
                ))}
            </g>
            <defs>
                <clipPath id="clip0_6_3504">
                    <rect width="18.368" height="22" fill="white" />
                </clipPath>
            </defs>
        </IconBase>
    );
}
