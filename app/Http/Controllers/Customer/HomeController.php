<?php

// @CODE:STORE-LIST-001:API | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md | TEST: tests/Feature/Customer/StoreListTest.php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 고객 홈 컨트롤러
 *
 * SPEC-STORE-LIST-001: 활성 Store 목록 조회
 * - Eager Loading으로 N+1 쿼리 방지
 * - 활성 Store만 표시 (is_active = true)
 * - 페이지네이션 적용 (10개/페이지)
 */
class HomeController extends Controller
{
    /**
     * 고객 홈 페이지 - 활성 Store 목록 조회
     *
     * @return Response Inertia response with stores data
     */
    public function index(): Response
    {
        // Organization Eager Loading으로 N+1 쿼리 방지
        // 활성 Store만 조회 (is_active = true)
        // 페이지네이션 적용 (10개/페이지)
        $lengthAwarePaginator = Store::with('organization')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Customer/Home', [
            'stores' => $lengthAwarePaginator,
        ])->rootView('customer.app');
    }
}
