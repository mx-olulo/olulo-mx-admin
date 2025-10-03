import IconBase from './IconBase';
import { getIconColor } from './get-icon-color';
import { svgPaths } from './svgPaths';

interface QrCodeIconProps {
    active: boolean;
}

/**
 * QR 코드 아이콘
 *
 * QR 코드 스캔 기능을 나타내는 아이콘입니다.
 */
export default function QrCodeIcon({ active }: QrCodeIconProps) {
    const color = getIconColor(active);

    return (
        <IconBase active={active}>
            <g>
                {/* 좌상단 박스 */}
                <path d={svgPaths.qrCode.topLeft.outer} fill={color} />
                <path d={svgPaths.qrCode.topLeft.inner} fill={color} />

                {/* 우상단 박스 */}
                <path d={svgPaths.qrCode.topRight.outer} fill={color} />
                <path d={svgPaths.qrCode.topRight.inner} fill={color} />

                {/* 우하단 박스 */}
                <path d={svgPaths.qrCode.bottomRight.outer} fill={color} />
                <path d={svgPaths.qrCode.bottomRight.inner} fill={color} />

                {/* 좌하단 점들 */}
                {svgPaths.qrCode.bottomLeftDots.map((dotPath, index) => (
                    <path key={index} d={dotPath} fill={color} />
                ))}
            </g>
        </IconBase>
    );
}
