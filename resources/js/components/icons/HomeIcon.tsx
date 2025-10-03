import IconBase from './IconBase';
import { getIconColor } from './get-icon-color';
import { svgPaths } from './svgPaths';

interface HomeIconProps {
    active: boolean;
}

/**
 * 홈 아이콘
 *
 * 집 모양의 아이콘으로 홈 탭을 나타냅니다.
 */
export default function HomeIcon({ active }: HomeIconProps) {
    const color = getIconColor(active);

    return (
        <IconBase active={active} width={21.904} height={22}>
            <g>
                <path d={svgPaths.home.outline} fill={color} />
            </g>
        </IconBase>
    );
}
