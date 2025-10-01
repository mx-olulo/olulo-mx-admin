<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 고객 홈 컨트롤러
 *
 * QR 코드 진입점 및 메인 페이지
 */
class HomeController extends Controller
{
    /**
     * QR 코드 진입점 페이지
     *
     * QR 코드 파라미터를 받아 처리하고 Home 페이지를 렌더링합니다.
     * - store: 매장 ID
     * - table: 테이블 ID
     * - seat: 좌석 번호
     */
    public function index(Request $request): Response
    {
        // QR 파라미터 추출
        $qrParams = [
            'store' => $request->query('store'),
            'table' => $request->query('table'),
            'seat' => $request->query('seat'),
        ];

        // null 값 제거
        $qrParams = array_filter($qrParams, fn ($value) => $value !== null);

        return Inertia::render('Customer/Home', [
            'qrParams' => count($qrParams) > 0 ? $qrParams : null,
        ])->rootView('customer.app');
    }
}
