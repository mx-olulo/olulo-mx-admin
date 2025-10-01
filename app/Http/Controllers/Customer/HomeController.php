<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 고객 홈 컨트롤러
 *
 * 홈 페이지
 */
class HomeController extends Controller
{
    /**
     * 홈 페이지
     *
     * TODO: QR 코드 처리는 별도 진행에서 구현
     */
    public function index(): Response
    {
        return Inertia::render('Customer/Home')
            ->rootView('customer.app');
    }
}
