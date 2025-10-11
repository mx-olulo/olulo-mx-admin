# RBAC 스코프 사용 예시

## 전체 플로우

```
1. 사용자 로그인
   ↓
2. SetScopeContext 미들웨어 실행 (매 요청마다)
   - 세션에서 current_team_id 읽기
   - setPermissionsTeamId() 호출
   ↓
3. 권한 체크
   - $user->hasRole('admin')
   - $user->can('products.create')
   ↓
4. 비즈니스 로직 실행
```

---

## 1. 역할 생성 (Seeder)

```php
// database/seeders/RoleSeeder.php
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 글로벌 역할 (모든 스코프에서 사용 가능)
        Role::create([
            'name' => 'super_admin',
            'team_id' => null,
            'scope_type' => null,
            'scope_ref_id' => null,
            'guard_name' => 'web',
        ]);

        // Organization 스코프 역할
        Role::create([
            'name' => 'org_admin',
            'team_id' => 100,  // 고유한 team_id
            'scope_type' => 'ORG',
            'scope_ref_id' => 1,  // organizations.id
            'guard_name' => 'web',
        ]);

        // Brand 스코프 역할
        Role::create([
            'name' => 'brand_manager',
            'team_id' => 200,
            'scope_type' => 'BRAND',
            'scope_ref_id' => 5,  // brands.id
            'guard_name' => 'web',
        ]);

        // Store 스코프 역할
        Role::create([
            'name' => 'store_staff',
            'team_id' => 300,
            'scope_type' => 'STORE',
            'scope_ref_id' => 10,  // stores.id
            'guard_name' => 'web',
        ]);
    }
}
```

---

## 2. 역할 할당

```php
// 사용자에게 역할 할당
$user = User::find(1);

// Organization 관리자 역할 할당
$orgAdminRole = Role::where('team_id', 100)->first();
$user->assignRole($orgAdminRole);

// 또는 이름으로 할당 (주의: team_id 컨텍스트 필요)
setPermissionsTeamId(100);
$user->assignRole('org_admin');
```

---

## 3. 스코프 전환 (UI)

```php
// Filament 컴포넌트 또는 컨트롤러
use App\Services\ScopeContextService;

class ScopeSwitcherController extends Controller
{
    public function switch(Request $request, ScopeContextService $scopeContext)
    {
        $teamId = $request->input('team_id');
        
        try {
            // 스코프 전환 (권한 검증 포함)
            $scopeContext->switchScope($teamId);
            
            return redirect()->back()->with('success', '스코프가 전환되었습니다.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    public function getAvailableScopes(ScopeContextService $scopeContext)
    {
        $user = auth()->user();
        $scopes = $scopeContext->getAvailableScopes($user);
        
        return response()->json($scopes);
        
        // 결과 예시:
        // [
        //     {
        //         "team_id": 100,
        //         "scope_type": "ORG",
        //         "scope_ref_id": 1,
        //         "role_name": "org_admin"
        //     },
        //     {
        //         "team_id": 200,
        //         "scope_type": "BRAND",
        //         "scope_ref_id": 5,
        //         "role_name": "brand_manager"
        //     }
        // ]
    }
}
```

---

## 4. 권한 체크

```php
// 컨트롤러에서
class ProductController extends Controller
{
    public function create()
    {
        // Spatie의 표준 권한 체크
        if (! auth()->user()->can('products.create')) {
            abort(403);
        }
        
        // 또는 미들웨어로
        // Route::post('/products', [ProductController::class, 'store'])
        //     ->middleware('can:products.create');
        
        return view('products.create');
    }
    
    public function index(ScopeContextService $scopeContext)
    {
        // 현재 스코프 정보 가져오기
        $currentScope = $scopeContext->getCurrentScope();
        
        if (! $currentScope) {
            return redirect()->route('scope.select')
                ->with('error', '스코프를 선택해주세요.');
        }
        
        // 현재 스코프의 상품만 조회
        $products = Product::where('store_id', $currentScope['scope_ref_id'])
            ->get();
        
        return view('products.index', [
            'products' => $products,
            'currentScope' => $currentScope,
        ]);
    }
}
```

---

## 5. Filament 통합

```php
// app/Filament/Pages/Dashboard.php
use App\Services\ScopeContextService;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function mount(ScopeContextService $scopeContext): void
    {
        $currentScope = $scopeContext->getCurrentScope();
        
        if (! $currentScope) {
            redirect()->route('scope.select');
        }
    }
    
    protected function getHeaderWidgets(): array
    {
        $scopeContext = app(ScopeContextService::class);
        $currentScope = $scopeContext->getCurrentScope();
        
        return [
            Widgets\ScopeInfo::make([
                'scope' => $currentScope,
            ]),
        ];
    }
}
```

---

## 6. 미들웨어 동작 확인

```php
// 테스트 또는 디버깅용
Route::get('/debug/scope', function (ScopeContextService $scopeContext) {
    return [
        'team_id' => getPermissionsTeamId(),
        'session_team_id' => session('current_team_id'),
        'current_scope' => $scopeContext->getCurrentScope(),
        'user_roles' => auth()->user()->roles->map(fn($role) => [
            'name' => $role->name,
            'team_id' => $role->team_id,
            'scope_type' => $role->scope_type,
            'scope_ref_id' => $role->scope_ref_id,
        ]),
    ];
})->middleware('auth');
```

---

## 7. 로그인 후 기본 스코프 설정

```php
// app/Http/Controllers/Auth/LoginController.php
use App\Services\ScopeContextService;

class LoginController extends Controller
{
    protected function authenticated(Request $request, $user)
    {
        $scopeContext = app(ScopeContextService::class);
        
        // 사용자의 첫 번째 역할을 기본 스코프로 설정
        $firstRole = $user->roles->whereNotNull('team_id')->first();
        
        if ($firstRole) {
            session(['current_team_id' => $firstRole->team_id]);
        }
        
        return redirect()->intended('/dashboard');
    }
}
```

---

## 8. API 사용 (Sanctum)

```php
// API 라우트
Route::middleware(['auth:sanctum'])->group(function () {
    // 스코프 전환
    Route::post('/scope/switch', function (Request $request, ScopeContextService $scopeContext) {
        $teamId = $request->input('team_id');
        
        try {
            $scopeContext->switchScope($teamId);
            
            return response()->json([
                'success' => true,
                'current_scope' => $scopeContext->getCurrentScope(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    });
    
    // 현재 스코프 조회
    Route::get('/scope/current', function (ScopeContextService $scopeContext) {
        return response()->json([
            'current_scope' => $scopeContext->getCurrentScope(),
        ]);
    });
});
```

---

## 9. 테스트

```php
// tests/Feature/ScopeContextTest.php
use App\Models\Role;
use App\Models\User;
use App\Services\ScopeContextService;

class ScopeContextTest extends TestCase
{
    public function test_user_can_switch_scope()
    {
        $user = User::factory()->create();
        
        // 역할 생성
        $role = Role::create([
            'name' => 'org_admin',
            'team_id' => 100,
            'scope_type' => 'ORG',
            'scope_ref_id' => 1,
        ]);
        
        $user->assignRole($role);
        
        $this->actingAs($user);
        
        // 스코프 전환
        $scopeContext = app(ScopeContextService::class);
        $scopeContext->switchScope(100);
        
        // 검증
        $this->assertEquals(100, getPermissionsTeamId());
        $this->assertEquals(100, session('current_team_id'));
        
        $currentScope = $scopeContext->getCurrentScope();
        $this->assertEquals('ORG', $currentScope['scope_type']);
        $this->assertEquals(1, $currentScope['scope_ref_id']);
    }
    
    public function test_middleware_sets_team_id()
    {
        $user = User::factory()->create();
        
        $role = Role::create([
            'name' => 'org_admin',
            'team_id' => 100,
            'scope_type' => 'ORG',
            'scope_ref_id' => 1,
        ]);
        
        $user->assignRole($role);
        
        session(['current_team_id' => 100]);
        
        $response = $this->actingAs($user)->get('/dashboard');
        
        // 미들웨어가 team_id를 설정했는지 확인
        $this->assertEquals(100, getPermissionsTeamId());
    }
}
```

---

## 핵심 포인트

### 1. 상태 관리

- **세션**: `current_team_id` (단일 값)
- **Spatie**: `setPermissionsTeamId()` (요청 생명주기)
- **미들웨어**: 매 요청마다 자동 설정

### 2. 권한 체크

```php
// Spatie 표준 방식 그대로 사용
$user->hasRole('admin');
$user->can('products.create');
Gate::allows('products.create');
```

### 3. 스코프 정보 조회

```php
// ScopeContextService 사용
$scopeContext->getCurrentScope();
// → ['team_id' => 100, 'scope_type' => 'ORG', 'scope_ref_id' => 1, 'role_name' => 'org_admin']
```

### 4. 스코프 전환

```php
// UI에서 호출
$scopeContext->switchScope($teamId);
// → 세션 저장 + Spatie 설정
```
