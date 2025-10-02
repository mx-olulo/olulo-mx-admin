import CustomerLayout from '@/Layouts/CustomerLayout';

/**
 * 이용약관 페이지
 *
 * TODO: 실제 이용약관 내용으로 교체 필요
 */
export default function Terms() {
    return (
        <CustomerLayout title="이용약관" showBack={true} showBottomNav={false}>
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                    <h1 className="text-3xl font-bold mb-6" style={{ fontFamily: 'Noto Sans' }}>
                        이용약관
                    </h1>

                    <div className="prose dark:prose-invert max-w-none">
                        <h2 className="text-2xl font-semibold mt-6 mb-4">제1조 (목적)</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            본 약관은 Olulo MX(이하 "회사")가 제공하는 음식 배달 서비스(이하 "서비스")의 이용과 관련하여
                            회사와 이용자 간의 권리, 의무 및 책임사항, 기타 필요한 사항을 규정함을 목적으로 합니다.
                        </p>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">제2조 (정의)</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            본 약관에서 사용하는 용어의 정의는 다음과 같습니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>"서비스"란 회사가 제공하는 음식 주문 및 배달 중개 서비스를 말합니다.</li>
                            <li>"이용자"란 본 약관에 따라 회사가 제공하는 서비스를 이용하는 자를 말합니다.</li>
                            <li>"매장"이란 서비스를 통해 음식을 판매하는 음식점을 말합니다.</li>
                            <li>"주문"이란 이용자가 서비스를 통해 매장의 음식을 구매하는 행위를 말합니다.</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">제3조 (약관의 효력 및 변경)</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            1. 본 약관은 서비스를 이용하고자 하는 모든 이용자에게 그 효력이 발생합니다.
                        </p>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            2. 회사는 필요한 경우 관련 법령을 위배하지 않는 범위에서 본 약관을 변경할 수 있으며,
                            약관이 변경되는 경우 변경사항을 서비스 내에 공지합니다.
                        </p>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">제4조 (서비스의 제공)</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            1. 회사는 다음과 같은 서비스를 제공합니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>음식 주문 중개 서비스</li>
                            <li>배달 중개 서비스</li>
                            <li>결제 대행 서비스</li>
                            <li>기타 회사가 정하는 서비스</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">제5조 (이용자의 의무)</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            이용자는 다음 행위를 하여서는 안 됩니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>타인의 정보 도용</li>
                            <li>회사의 서비스 정보 임의 변경</li>
                            <li>회사가 정한 정보 이외의 정보 송신 또는 게시</li>
                            <li>회사와 기타 제3자의 저작권 등 지적재산권 침해</li>
                            <li>기타 관련 법령에 위배되는 행위</li>
                        </ul>

                        <div className="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                최종 업데이트: 2025년 10월 2일
                            </p>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                ※ 본 이용약관은 임시 버전입니다. 실제 서비스 런칭 전 법률 자문을 받아 정식 버전으로 교체될 예정입니다.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </CustomerLayout>
    );
}
