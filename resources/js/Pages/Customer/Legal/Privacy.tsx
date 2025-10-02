import CustomerLayout from '@/Layouts/CustomerLayout';

/**
 * 개인정보처리방침 페이지
 *
 * TODO: 실제 개인정보처리방침 내용으로 교체 필요
 */
export default function Privacy() {
    return (
        <CustomerLayout title="개인정보처리방침" showBack={true} showBottomNav={false}>
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                    <h1 className="text-3xl font-bold mb-6" style={{ fontFamily: 'Noto Sans' }}>
                        개인정보처리방침
                    </h1>

                    <div className="prose dark:prose-invert max-w-none">
                        <h2 className="text-2xl font-semibold mt-6 mb-4">1. 개인정보의 수집 및 이용 목적</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            Olulo MX(이하 "회사")는 다음의 목적을 위하여 개인정보를 처리합니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>서비스 제공 및 본인 확인</li>
                            <li>주문 및 배달 서비스 제공</li>
                            <li>결제 및 정산 처리</li>
                            <li>고객 문의 응대 및 불만 처리</li>
                            <li>서비스 개선 및 신규 서비스 개발</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">2. 수집하는 개인정보 항목</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            회사는 다음과 같은 개인정보를 수집합니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li><strong>필수 항목:</strong> 이름, 이메일 주소, 전화번호, 배달 주소</li>
                            <li><strong>선택 항목:</strong> 프로필 사진, 생년월일</li>
                            <li><strong>자동 수집 항목:</strong> IP 주소, 쿠키, 서비스 이용 기록, 기기 정보</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">3. 개인정보의 보유 및 이용 기간</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            회사는 원칙적으로 개인정보 수집 및 이용 목적이 달성된 후에는 해당 정보를 지체 없이 파기합니다.
                            다만, 관련 법령에 따라 다음과 같이 일정 기간 보관합니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>계약 또는 청약철회 등에 관한 기록: 5년</li>
                            <li>대금결제 및 재화 등의 공급에 관한 기록: 5년</li>
                            <li>소비자의 불만 또는 분쟁처리에 관한 기록: 3년</li>
                            <li>전자상거래 등에서의 표시·광고에 관한 기록: 6개월</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">4. 개인정보의 제3자 제공</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            회사는 이용자의 개인정보를 원칙적으로 외부에 제공하지 않습니다.
                            다만, 다음의 경우에는 예외로 합니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>이용자가 사전에 동의한 경우</li>
                            <li>법령의 규정에 의거하거나, 수사 목적으로 법령에 정해진 절차와 방법에 따라 수사기관의 요구가 있는 경우</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">5. 개인정보의 파기 절차 및 방법</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            회사는 개인정보 보유기간의 경과, 처리목적 달성 등 개인정보가 불필요하게 되었을 때에는
                            지체없이 해당 개인정보를 파기합니다.
                        </p>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">6. 이용자의 권리</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            이용자는 언제든지 다음의 권리를 행사할 수 있습니다:
                        </p>
                        <ul className="list-disc list-inside mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>개인정보 열람 요구</li>
                            <li>개인정보 정정·삭제 요구</li>
                            <li>개인정보 처리정지 요구</li>
                        </ul>

                        <h2 className="text-2xl font-semibold mt-6 mb-4">7. 개인정보 보호책임자</h2>
                        <p className="mb-4 text-gray-700 dark:text-gray-300">
                            회사는 개인정보 처리에 관한 업무를 총괄해서 책임지고, 개인정보 처리와 관련한
                            이용자의 불만처리 및 피해구제 등을 위하여 아래와 같이 개인정보 보호책임자를 지정하고 있습니다.
                        </p>
                        <div className="bg-gray-100 dark:bg-gray-700 p-4 rounded-xl mb-4">
                            <p className="text-gray-700 dark:text-gray-300">
                                <strong>개인정보 보호책임자:</strong> [담당자명]
                            </p>
                            <p className="text-gray-700 dark:text-gray-300">
                                <strong>이메일:</strong> privacy@olulo.com.mx
                            </p>
                            <p className="text-gray-700 dark:text-gray-300">
                                <strong>전화:</strong> +52 [전화번호]
                            </p>
                        </div>

                        <div className="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                최종 업데이트: 2025년 10월 2일
                            </p>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                ※ 본 개인정보처리방침은 임시 버전입니다. 실제 서비스 런칭 전 법률 자문을 받아 정식 버전으로 교체될 예정입니다.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </CustomerLayout>
    );
}
