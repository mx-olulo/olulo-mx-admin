# RBAC (Teams + Scopes) TODO 리스트

- **스코프**: Spatie Permission v6 Teams 활성화 + 다형 스코프(`scopes`) 도입
- **가정**: 운영 데이터 없음 → DB 리프레시 가능

## 1) Config
- [ ] `config/permission.php`에서 `teams = true` 설정
- [ ] `team_resolver = App\Permissions\CurrentScopeResolver::class` 지정
- [ ] 캐시 정책 합의(`display_permission_in_exception`, `display_role_in_exception`)

## 2) Database
- [ ] `scopes` 테이블 마이그레이션 추가 (PK/Unique/인덱스 포함)
- [ ] Spatie 권한 테이블이 팀 컬럼 포함으로 생성되도록 확인
- [ ] `migrate:fresh` 실행 계획 준비(개발/CI 환경)

## 3) Application (Infra)
- [ ] `App/Permissions/CurrentScopeResolver` 골격 추가 및 서비스 컨테이너 등록
- [ ] `App/Http/Middleware/SetScopeContext` 추가, `Http/Kernel.php`에 등록
- [ ] 컨텍스트 전달 규약 확정: `X-Scope-Type`, `X-Scope-Id` (임시), 세션/쿼리 병행 여부

## 4) Seeding
- [ ] 글로벌 롤/권한 시드(`team_id = null`) 필요 시 정의 (예: `admin`, `customer`)
- [ ] 스코프 전용 롤 시드(예: `org_admin`, `brand_manager`, `store_staff`) 설계
- [ ] 샘플 스코프(Org/Brand/Store) 데이터 및 롤 매핑 시드 추가

## 5) Policies / Authorization
- [ ] (초기) 스코프 내 명시적 권한만 허용, 상위→하위 계승은 비활성
- [ ] (후속) 계승 규칙(Org→Brand/Store, Brand→Store) 정책으로 도입하는 옵션 설계

## 6) UI/Developer Experience
- [ ] 컨텍스트 스위처(상단) UX 정의 및 화면 라우팅 규칙 합의
- [ ] 403 처리 시 컨텍스트 전환 CTA 제공
- [ ] 개발자 가이드: 컨텍스트 설정/전파/검증 흐름 문서화

## 7) Testing / QA
- [ ] 컨텍스트별 `hasRole`/`can` 동작 수동/자동 테스트 케이스 작성
- [ ] 컨텍스트 미설정/오설정(잘못된 team) 시 403 응답 검증
- [ ] 캐시 무효화/권한 변경 반영 시간 확인

## 8) Docs
- [ ] `docs/roles-and-permissions.md`에 Teams + Scopes 전환 내용 추가
- [ ] 개발 온보딩 문서에 컨텍스트 해설 및 예제 시나리오 추가

## 9) Rollout
- [ ] feature 브랜치 `feature/rbac-teams-scopes` 운영
- [ ] CI에서 `migrate:fresh` + 시드 실행 검증
- [ ] QA 후 `develop` → `main` 병합 절차 정의

## 10) 최초 로그인 온보딩(무소속 사용자)
- [ ] 무소속 사용자 감지 미들웨어/가드(개념) 흐름 합의 (`CheckFirstLogin` 개념)
- [ ] 온보딩 선택 화면(스토어/조직 생성) UX 개념 합의
- [ ] 스토어/조직 생성 위자드 범위(필드/단계) 정의(코드 없이 개념)
- [ ] 위자드 완료 후 컨텍스트(`scopes.id`) 바인딩 정책 합의
- [ ] 온보딩 중 보호 라우트 접근 제한/리다이렉트 규칙 합의

## 11) 브랜치/PR 운영(세분화)
- [ ] 메인 기능 브랜치: `feature/rbac-teams-scopes`
- [ ] 서브 브랜치 생성/운영: `config`, `migrations`, `resolver-middleware`, `seeding`, `onboarding`, `tests`, `docs`
- [ ] 서브 PR 단위 리뷰/수정 후 메인 기능 브랜치로 병합 → 다음 단계 진행
