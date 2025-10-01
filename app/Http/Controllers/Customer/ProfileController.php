<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 고객 개인 영역 컨트롤러
 *
 * 마이페이지, 주문 내역, 프로필 등
 */
class ProfileController extends Controller
{
    /**
     * 주문 내역 페이지
     */
    public function orders(Request $request): Response
    {
        return Inertia::render('My/Orders', [
            'user' => $request->user(),
        ]);
    }
}
