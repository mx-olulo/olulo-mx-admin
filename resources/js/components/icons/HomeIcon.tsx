import IconBase from './IconBase';
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
    return (
        <IconBase active={active} width={21.904} height={22}>
            <g>
                <path d={svgPaths.home.outline} fill="currentColor" />
            </g>
        </IconBase>
    );
}
