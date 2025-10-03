import IconBase from './IconBase';
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
    return (
        <IconBase active={active}>
            <g>
                {/* 좌상단 박스 */}
                <path d={svgPaths.qrCode.topLeft.outer} fill="currentColor" />
                <path d={svgPaths.qrCode.topLeft.inner} fill="currentColor" />

                {/* 우상단 박스 */}
                <path d={svgPaths.qrCode.topRight.outer} fill="currentColor" />
                <path d={svgPaths.qrCode.topRight.inner} fill="currentColor" />

                {/* 우하단 박스 */}
                <path d={svgPaths.qrCode.bottomRight.outer} fill="currentColor" />
                <path d={svgPaths.qrCode.bottomRight.inner} fill="currentColor" />

                {/* 좌하단 점들 */}
                {svgPaths.qrCode.bottomLeftDots.map((dotPath, index) => (
                    <path key={index} d={dotPath} fill="currentColor" />
                ))}
            </g>
        </IconBase>
    );
}
