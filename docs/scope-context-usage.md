# 스코프 컨텍스트 사용 가이드

## 개요

`ScopeContextService`는 세션 기반으로 현재 사용자의 활성 스코프(Organization/Brand/Store)를 일원화하여 관리합니다.

## 아키텍처

```
사용자 로그인
    ↓
스코프 선택 (UI 스위처 또는 자동)
    ↓
ScopeContextService::setScope()
    ↓
세션 저장 (scope_type, scope_id, team_id)
    ↓
setPermissionsTeamId(team_id) 호출 (Spatie 공식 방식)
    ↓
권한 체크 (hasRole/can)
```

## 기본 사용법

### 1. 헬퍼 함수 사용 (권장)

```php
// 서비스 인스턴스 가져오기
$service = scopeContext();

// 현재 스코프 정보 조회
$scopeType = scopeContext()->getCurrentScopeType(); // 'ORG'|'BRAND'|'STORE'|null
$scopeId = scopeContext()->getCurrentScopeId();     // 실제 엔터티 PK
$teamId = scopeContext()->getCurrentTeamId();       // scopes.id (Spatie team_id)

// 또는 한 번에
$scope = scopeContext()->getCurrentScope();
// ['type' => 'STORE', 'id' => 123, 'team_id' => 456]

// 컨텍스트 존재 여부 확인
if (scopeContext()->hasScope()) {
    // 스코프가 설정되어 있음
}

// Spatie Permission용 team_id만 필요한 경우
$teamId = currentScopeTeamId();
```

### 2. 의존성 주입 사용

```php
use App\Services\ScopeContextService;

class StoreController extends Controller
{
    public function __construct(
        private ScopeContextService $scopeContext
    ) {}

    public function index()
    {
        $currentScope = $this->scopeContext->getCurrentScope();
        
        // 현재 스코프 내의 데이터만 조회
        $stores = Store::where('id', $currentScope['id'])->get();
    }
}
```

## 주요 시나리오

### 시나리오 1: 스코프 설정 (UI 스위처)

```php
// Filament 컴포넌트 또는 컨트롤러
public function switchScope(Request $request)
{
    $scopeType = $request->input('scope_type'); // 'STORE'
    $scopeId = $request->input('scope_id');     // 123
    
    // 권한 검증
    if (!scopeContext()->userCanAccessScope(auth()->user(), $scopeType, $scopeId)) {
        abort(403, '해당 스코프에 접근 권한이 없습니다.');
    }
    
    // 스코프 설정
    scopeContext()->setScope($scopeType, $scopeId);
    
    return redirect()->back();
}
```

### 시나리오 2: 로그인 후 기본 스코프 설정

```php
// LoginController 또는 미들웨어
public function authenticated(Request $request, $user)
{
    // 사용자의 첫 번째 멤버십을 기본 스코프로 설정
    if (!scopeContext()->hasScope()) {
        scopeContext()->setDefaultScopeForUser($user);
    }
}
```

### 시나리오 3: 미들웨어에서 컨텍스트 검증

```php
// SetScopeContext 미들웨어
public function handle($request, Closure $next)
{
    if (!scopeContext()->hasScope()) {
        // 스코프 미설정 시 선택 화면으로
        return redirect()->route('scope.select');
    }
    
    $scope = scopeContext()->getCurrentScope();
    
    // 사용자 권한 재검증
    if (!scopeContext()->userCanAccessScope(auth()->user(), $scope['type'], $scope['id'])) {
        // 권한 상실 시 컨텍스트 초기화
        scopeContext()->clearScope();
        return redirect()->route('scope.select')
            ->with('error', '해당 스코프에 대한 접근 권한이 없습니다.');
    }
    
    return $next($request);
}
```

### 시나리오 4: 로그아웃 시 컨텍스트 초기화

```php
// LogoutController
public function logout(Request $request)
{
    scopeContext()->clearScope();
    
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect('/');
}
```

### 시나리오 5: Spatie Permission과 통합

```php
// 컨트롤러 또는 정책
public function store(Request $request)
{
    // 현재 스코프 컨텍스트에서 권한 체크
    // ScopeContextService::setScope()가 이미 setPermissionsTeamId()를 호출했으므로
    // Spatie가 자동으로 현재 team_id 컨텍스트에서 권한 체크
    
    if (!auth()->user()->can('stores.create')) {
        abort(403);
    }
    
    // 현재 스코프 내에서만 생성
    $store = Store::create([
        'scope_type' => scopeContext()->getCurrentScopeType(),
        'scope_id' => scopeContext()->getCurrentScopeId(),
        // ...
    ]);
}
```

## 세션 키 구조

```php
// 세션에 저장되는 키
'current_scope_type'    => 'STORE'  // 스코프 타입
'current_scope_id'      => 123      // 실제 엔터티 PK
```

## 주의사항

### 1. 세션 기반이므로 서버 사이드 렌더링에 적합
- Filament와 같은 전통적인 Laravel 앱에 최적화
- API 전용 앱은 토큰 기반 컨텍스트 전달 고려 필요

### 2. team_id 조회
- `getCurrentTeamId()`는 매번 `scopes` 테이블을 조회합니다
- `scopes` 테이블은 작고 인덱스가 있어 성능 영향 미미
- Spatie Permission은 자체적으로 역할/권한을 메모리에 캐싱 (v4.4.0+)

### 3. 권한 검증 시점
- 스코프 설정 시: `userCanAccessScope()`로 사전 검증
- 미들웨어: 매 요청마다 재검증 (권한 변경 반영)
- Spatie Permission: 실제 권한 체크 시점

### 4. 글로벌 컨텍스트 (team_id = null)
- 스코프가 설정되지 않으면 `null` 반환
- 글로벌 역할(예: `super_admin`)은 team_id=null로 부여

## 테스트

```php
// 테스트에서 스코프 설정
public function test_store_creation_in_scope()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // 스코프 설정
    scopeContext()->setScope('STORE', 123, 456);
    
    // 권한 체크
    $this->assertTrue(scopeContext()->hasScope());
    $this->assertEquals('STORE', scopeContext()->getCurrentScopeType());
    $this->assertEquals(456, currentScopeTeamId());
}
```

## 확장 포인트

### 커스텀 스코프 타입 추가

```php
// ScopeContextService에 메서드 추가
public function isBrandScope(): bool
{
    return $this->getCurrentScopeType() === 'BRAND';
}
```

### 스코프 전환 이벤트

```php
// setScope() 메서드에 이벤트 발생 추가
event(new ScopeChanged($scopeType, $scopeId));
```

## 관련 파일

- `app/Services/ScopeContextService.php` - 핵심 서비스
- `app/Models/Scope.php` - 스코프 모델
- `app/Support/helpers.php` - 헬퍼 함수
- `app/Providers/AppServiceProvider.php` - 서비스 등록
- `config/permission.php` - Spatie 설정
- `database/migrations/*_create_scopes_table.php` - scopes 테이블 마이그레이션

## Spatie 통합 방식

### setPermissionsTeamId() 사용 (공식 방식)

`ScopeContextService::setScope()`가 호출되면 자동으로 `setPermissionsTeamId()`를 호출하여 Spatie에 현재 team_id를 설정합니다.

```php
// ScopeContextService::setScope() 내부
public function setScope(string $scopeType, int $scopeId, ?int $teamId = null): void
{
    // 세션에 저장
    Session::put([
        'current_scope_type' => $scopeType,
        'current_scope_id' => $scopeId,
    ]);

    // Spatie Permission에 team_id 설정
    if ($teamId !== null) {
        setPermissionsTeamId($teamId);
    } else {
        $resolvedTeamId = $this->getCurrentTeamId();
        if ($resolvedTeamId !== null) {
            setPermissionsTeamId($resolvedTeamId);
        }
    }
}
```

이후 모든 `hasRole()`, `can()` 호출은 자동으로 현재 team_id 컨텍스트에서 실행됩니다.
