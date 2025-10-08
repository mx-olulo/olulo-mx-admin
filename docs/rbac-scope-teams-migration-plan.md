# Spatie Permission Teams 기반 스코프형 RBAC 전환 실행 계획

- **목표**
  - `Organization/Brand/Store` 단위의 스코프 기반 RBAC를 Spatie Permission v6의 Teams 기능으로 구현한다.
  - 운영 데이터 없음 가정. 마이그레이션 리프레시 가능.

- **핵심 결정(Architecture Decision)**
  - Spatie `teams = true` 활성화.
  - 단일 `scopes` 테이블을 생성하여 team_id의 실체를 다형 스코프로 정규화.
  - `team_resolver`를 커스텀 구현하여 요청 컨텍스트(Org/Brand/Store + id) → `scopes.id`를 반환.
  - 컨텍스트 미들웨어로 사용자 요청에 스코프를 주입 및 유효성 검증.

- **영향 범위(Data/Config/Code)**
  - 데이터베이스:
    - Spatie 권한 테이블 5종을 teams 활성 상태로 재생성(`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`).
    - 신규 `scopes` 테이블 추가.
  - 설정:
    - `config/permission.php` → `'teams' => true`, `team_resolver`에 커스텀 리졸버 지정.
  - 애플리케이션:
    - `app/Permissions/CurrentScopeResolver`(예시 경로) 구현.
    - `app/Http/Middleware/SetScopeContext` 구현(헤더/세션/쿼리에서 스코프 수신, 검증, 컨텍스트 바인딩).
    - 시더에서 팀(스코프) 단위 롤/권한 생성 정책 반영.

---

## 1) 사전 조건 및 가정
- **운영 데이터 없음** → `migrate:fresh` 허용.
- `users.id`는 bigint이며 Spatie 테이블도 bigint 사용. UUID 전환 계획 없음.
- 컨텍스트 전달 방식: 임시로 HTTP 헤더 `X-Scope-Type`, `X-Scope-Id`를 사용. (필요 시 세션/쿼리 파라미터 병행)

## 2) 데이터 모델 설계(요약)
- `scopes` 테이블(권장 스키마 개념)
  - PK: `id` (bigint, auto increment)
  - `scope_type`: ENUM('ORG','BRAND','STORE') 또는 VARCHAR(10)
  - `scope_ref_id`: BIGINT (실제 org/brand/store PK)
  - Unique: (`scope_type`, `scope_ref_id`)
  - Index: `scope_type`, `scope_ref_id`
- Spatie 권한 테이블: teams 활성 시 다음 변경점 반영
  - `roles`: `team_id` 컬럼 및 (`team_id`, `name`, `guard_name`) unique
  - `model_has_roles`, `model_has_permissions`: 복합 PK에 `team_id` 포함

## 3) 실행 단계(순서 보장)
- **[Step 0] 브랜치 전략**
  - feature 브랜치 생성: `feature/rbac-teams-scopes`

- **[Step 1] 설정 변경**
  - `config/permission.php`
    - `'teams' => true`
    - `'team_resolver' => App\Permissions\CurrentScopeResolver::class` 지정
  - 캐시 초기화 계획 수립(`config:clear`, `cache:clear`)

- **[Step 2] 마이그레이션 준비**
  - 기존 Spatie 마이그레이션 확인: `database/migrations/2025_09_26_152355_create_permission_tables.php`
  - teams 활성 상태에서 재생성 예정 → `migrate:fresh`로 테이블 드롭/재생성

- **[Step 3] 신규 마이그레이션 추가**
  - `database/migrations/xxxx_xx_xx_xxxxxx_create_scopes_table.php`
    - 위 설계의 `scopes` 테이블 생성

- **[Step 4] 리졸버/미들웨어 골격 추가**
  - `app/Permissions/CurrentScopeResolver.php`
    - 요청 컨텍스트에서 `X-Scope-Type`, `X-Scope-Id` 추출 → `scopes` 조회 → 해당 `id` 반환
    - 유효하지 않으면 null 또는 예외 정책 정의(권장: 명시적 403)
  - `app/Http/Middleware/SetScopeContext.php`
    - 사용자 멤버십 검증 위치(추후 `Membership`/조직 모델 추가 시 연결)
    - 컨텍스트를 리퀘스트 컨테이너/서비스로 바인딩하여 어디서든 참조 가능하게 함

- **[Step 5] 시드 전략 반영**
  - 글로벌 공용 롤(예: `admin`, `customer`)이 필요하면 `team_id = null`로 생성
  - 스코프 전용 롤(예: `org_admin`, `brand_manager`, `store_staff`)은 컨텍스트(team)별 생성
  - 최소 시드: 기본 권한 세트와 대표 스코프(샘플 Org/Brand/Store) + 역할 생성

- **[Step 6] 마이그레이션/리프레시**
  - 설정 캐시/앱 캐시 초기화 후 DB 리프레시
  - artisan 명령 시퀀스는 운영 문서에 별도 명시(여기서는 개념만 표기)

- **[Step 7] 검증(수동/자동 테스트)**
  - `User`에 특정 스코프(team)에서 롤 부여 시, 해당 컨텍스트에서만 `hasRole`/`can`이 참이 되는지 확인
  - 컨텍스트 미설정/오설정 시 접근 차단/403 동작 확인

- **[Step 8] 문서화/가이드 반영**
  - `docs/roles-and-permissions.md`에 teams 전환 사항과 컨텍스트 사용법 추가
  - 개발자 온보딩 문서에 컨텍스트/팀 개념 설명 추가

---

## 4) 체크리스트
- **[Config]**
  - [ ] `config/permission.php` teams=true
  - [ ] `team_resolver`에 커스텀 지정
  - [ ] `display_permission_in_exception/display_role_in_exception` 필요 시 정책 결정
- **[DB]**
  - [ ] `scopes` 테이블 생성
  - [ ] Spatie 권한 테이블이 team 열 포함으로 생성되었는지 확인
  - [ ] 인덱스/유니크 제약 검증
- **[App]**
  - [ ] `CurrentScopeResolver` 구현 및 등록
  - [ ] `SetScopeContext` 미들웨어 등록(`Http/Kernel.php`)
  - [ ] 멤버십 검증 포인트 설계(후속 단계에서 Organization/Brand/Store 모델/멤버십 연결)
- **[Seed]**
  - [ ] 기본 롤/권한 시드(글로벌 vs 스코프 구분)
  - [ ] 샘플 스코프 시드(Org/Brand/Store)
- **[Test]**
  - [ ] 컨텍스트별 `hasRole`/`can` 동작 검증
  - [ ] 컨텍스트 전환 시 메뉴/리소스 스코핑 확인

---

## 5) 리스크 & 대응
- **권한 계승(Org→Brand/Store)**
  - Spatie 기본 동작 아님 → 별도 정책/게이트에서 구현 필요
  - 초기 릴리스에서는 계승 비활성/명시적 권한만 사용을 권장, 이후 단계적 도입
- **롤 중복 관리**
  - 팀 단위 유니크 제약으로 롤 이름 중복 관리 가능. 네이밍 컨벤션 수립 권장(예: `org_admin`, `brand_manager`, `store_staff`).

## 6) 롤아웃/롤백
- **롤아웃**: `feature` → `develop` 머지, QA 검증 후 `main` 반영
- **롤백**: teams=false로 되돌릴 경우 DB 재생성 필요. 본 단계에서는 운영 데이터가 없으므로 위험 낮음.

## 7) 후속 작업(선택)
- Organization/Brand/Store 및 Membership 도입(도메인 스키마 연계)
- 컨텍스트 선택 UI(상단 스위처) 및 접근 제어 UX(403 + 컨텍스트 전환 CTA)
- 정책 오버라이드 설계(`policy_overrides`)

---

## 8) 최초 로그인 온보딩(무소속 사용자)
- **배경**: 파이어베이스 소셜 로그인으로 자동 가입 시, 사용자에게 소속된 스코프(Organization/Brand/Store)가 없을 수 있음.
- **목표**: 무소속 사용자에게 빈 화면 대신 선택지 기반의 온보딩 위자드를 제공하여 즉시 가치 창출 경로(스토어 또는 조직 생성)로 유도.

### 8.1 UX 플로우(개념)
```mermaid
graph TD
  A[로그인 완료] --> B{사용자에 연결된 Membership/Scope 존재?}
  B -- 아니오 --> C[온보딩 선택 화면]
  C -->|개인 스토어 시작| D[스토어 생성 위자드]
  C -->|조직으로 시작| E[조직 생성 위자드]
  D --> F[생성 완료: 현재 스코프로 바인딩]
  E --> F
  F --> G[대시보드(컨텍스트 적용)]
  B -- 예 --> G
```

### 8.2 기술 훅(코드 없이 개념)
- **무소속 감지**: `User`에 연결된 멤버십/스코프 조회 결과가 없으면 온보딩 라우트로 리다이렉트.
- **미들웨어**: `CheckFirstLogin`(개념) → 무소속이면 `onboarding.choose-type`로 이동.
- **컨텍스트 연계**: 위자드 완료 시 생성된 엔터티를 `scopes`에 등록하고 해당 `scopes.id`를 활성 컨텍스트로 설정.
- **접근 제어**: 온보딩 중에는 일반 보호 라우트 접근을 제한하고, 온보딩 라우트는 인증만으로 접근 허용.
- **스킵 옵션(선택)**: "나중에" 선택 시 글로벌 컨텍스트(null) 허용 여부를 정책으로 명시.

### 8.3 생성 위자드 범위(개념)
- **스토어 생성**: 최소 필수 정보(이름/지역/연락처 등)만 수집. 완료 시 `STORE` 스코프 생성.
- **조직 생성**: 조직 기본 정보 + 초기 관리자 자신 할당. 필요 시 브랜드/스토어는 후속 단계로 유도.

### 8.4 테스트 관점
- 무소속 사용자 로그인 → 온보딩 선택 화면 노출.
- 스토어 생성 완료 후 `hasRole/can`이 신규 스코프에서만 동작함을 확인.
- 온보딩 중 보호 라우트 접근 시 적절한 차단/리다이렉트.

---

## 9) 브랜치/PR 운영 가이드
- **메인 기능 브랜치**: `feature/rbac-teams-scopes`
- **서브 브랜치(권장 예시)**
  - `feature/rbac-teams-scopes/config`
  - `feature/rbac-teams-scopes/migrations`
  - `feature/rbac-teams-scopes/resolver-middleware`
  - `feature/rbac-teams-scopes/seeding`
  - `feature/rbac-teams-scopes/onboarding` ← 최초 로그인 온보딩 문서/라우팅/가드(개념) 작업
  - `feature/rbac-teams-scopes/tests`
  - `feature/rbac-teams-scopes/docs`
- **프로세스**: 서브 브랜치 단위로 서브 PR 생성 → 리뷰/수정 → 메인 기능 브랜치로 머지 → 다음 단계 진행.
