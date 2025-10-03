import IconBase from './IconBase';
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
    return (
        <IconBase active={active}>
            <g>
                <path d={svgPaths.settings.outer} fill="currentColor" />
                <path d={svgPaths.settings.inner} fill="currentColor" />
            </g>
        </IconBase>
    );
}
