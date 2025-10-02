import IconBase, { getIconColor } from './IconBase';
import { svgPaths } from './svgPaths';

interface SettingsIconProps {
    active: boolean;
}

/**
 * 설정 아이콘
 *
 * 설정 메뉴를 나타내는 톱니바퀴 아이콘입니다.
 */
export default function SettingsIcon({ active }: SettingsIconProps) {
    const color = getIconColor(active);

    return (
        <IconBase active={active}>
            <g>
                <path d={svgPaths.settings.outer} fill={color} />
                <path d={svgPaths.settings.inner} fill={color} />
            </g>
        </IconBase>
    );
}
