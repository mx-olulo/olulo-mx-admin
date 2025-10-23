# 기능 명세: 3티어 사용자 권한 모델 리팩토링

**기능 브랜치**: `001-refactor-user-rbac-model`
**생성일**: 2025-10-20
**최종 수정일**: 2025-10-23
**상태**: 진행 중
**입력**: 사용자 설명: "현재의 사용자 및 권한 관리 모델을 리팩토링 한다. 1. Spatie Permissions 는 제거한다. 2. 3티어 모델로 어드민, 유저, 커스토머를 분리한다. 3. 어드민은 필라멘트 테넌시의 조직,브랜드,스토어를 멀티테넌시로 엑세스한다. 4. 어드민은 M:N 대응의 권한 모델을 가진다. 하나의 사용자가 여러 테넌시에 엑세스하고, 개별 역할을 가진다. 5. 유저는 시스템, 플랫폼 관리 조직만 엑세스 한다. 6. 개발 중에는 폴리시는 제거한다. 7. 기존 마이그레이션은 필요시 제거나 수정해도 된다. 기존 데이터의 존재는 무시한다. 8. 경량화와 제거를 우선으로 한다. 커스토머의 파이어베이스 엑세스는 유효하다."

## HISTORY

### 2025-10-23 - User 모델 리팩토링 (PR #69)
- **REFACTOR**: User 모델 복잡도 감소를 위한 Trait 분리
- **변경사항**:
  - `HasTenantRelations` Trait 추가 (81 LOC)
    - tenantUsers() 관계 정의
    - getTenantsByType() 메서드
    - getRoleForTenant() 메서드
  - `HasTenantPermissions` Trait 추가 (80 LOC)
    - hasRoleForTenant() 메서드
    - canManageTenant() 메서드
    - canViewTenant() 메서드
    - hasGlobalRole() 메서드
  - User 모델에서 114줄의 코드를 2개 Trait으로 분리
- **품질 검증**:
  - 테스트: 16개 통과, 41개 assertion
  - PHPStan 레벨 5 통과
  - Laravel Pint 코딩 스타일 통과
  - Rector 코드 품질 검사 통과
- **관련 PR**: https://github.com/mx-olulo/olulo-mx-admin/pull/69
- **AUTHOR**: @Claude
- **REVIEW**: 리뷰 대기중

## 사용자 시나리오 & 테스팅 *(필수)*

### 사용자 스토리 1 - Admin 멀티테넌트 접근 (우선순위: P1)

Admin 사용자는 여러 Organization, Brand, Store에 동시에 접근하고, 각 테넌트에서 서로 다른 역할을 수행한다. 예를 들어, 한 사용자가 Organization A에서는 관리자이지만 Organization B에서는 뷰어 권한만 가질 수 있다.

**우선순위 이유**: 멀티테넌시는 시스템의 핵심 가치이며, Admin 사용자의 기본 작업 흐름이다. 이것이 없으면 시스템의 기본 운영이 불가능하다.

**독립 테스트**: Admin 사용자를 생성하고 여러 테넌트에 역할을 할당한 후, 각 테넌트 패널에 로그인하여 역할에 맞는 권한으로 리소스에 접근할 수 있는지 확인한다.

**수락 시나리오**:

1. **Given** Admin 사용자가 Organization A에 "owner" 역할로, Organization B에 "viewer" 역할로 할당됨, **When** Admin이 Filament Organization 패널에 로그인, **Then** 테넌트 선택 화면에 Organization A와 B가 모두 표시됨
2. **Given** Admin이 Organization A를 선택함, **When** 리소스 관리 화면에 접근, **Then** owner 권한으로 모든 CRUD 작업이 가능함
3. **Given** Admin이 Organization B를 선택함, **When** 리소스 관리 화면에 접근, **Then** viewer 권한으로 읽기만 가능하고 생성/수정/삭제 버튼이 비활성화됨
4. **Given** Admin 사용자가 Brand A, Store B에도 각각 역할을 보유함, **When** Admin이 Brand/Store 패널에 접근, **Then** 해당 테넌트 목록이 표시되고 역할에 맞는 권한으로 접근 가능함

---

### 사용자 스토리 2 - User 글로벌 접근 제한 (우선순위: P1)

User 사용자는 Platform 및 System 관리 조직에만 접근하며, 테넌트별 멀티테넌시 기능을 사용하지 않는다. Platform Admin은 전체 시스템 설정을 관리하고, System Admin은 시스템 레벨 운영 작업을 수행한다.

**우선순위 이유**: 시스템 운영을 위한 글로벌 관리자 역할은 Admin과 분리되어야 하며, 보안 및 책임 분리 원칙에 필수적이다.

**독립 테스트**: platform_admin 역할을 가진 User를 생성하고, Platform 패널에 로그인하여 글로벌 설정에 접근 가능하나 Organization/Brand/Store 패널에는 접근 불가함을 확인한다.

**수락 시나리오**:

1. **Given** User에게 "platform_admin" 역할이 할당됨, **When** User가 Platform 패널에 로그인 시도, **Then** 접근이 허용되고 전체 시스템 설정 메뉴가 표시됨
2. **Given** User에게 "system_admin" 역할이 할당됨, **When** User가 System 패널에 로그인 시도, **Then** 접근이 허용되고 시스템 운영 메뉴가 표시됨
3. **Given** User가 platform_admin 역할만 보유함, **When** Organization/Brand/Store 패널에 접근 시도, **Then** 접근이 거부되고 "권한 없음" 메시지가 표시됨
4. **Given** User가 여러 글로벌 역할을 보유함, **When** 패널 선택 화면에 접근, **Then** Platform 및 System 패널만 표시되고 테넌트 패널은 표시되지 않음

---

### 사용자 스토리 3 - Customer Firebase 인증 유지 (우선순위: P2)

Customer 사용자는 Firebase를 통해 인증되며, Filament Admin 패널에는 접근하지 않는다. Customer의 Firebase 인증 흐름은 기존 방식을 유지하되, 새로운 권한 모델과 독립적으로 작동한다.

**우선순위 이유**: Customer는 별도의 모바일/웹 앱을 통해 접근하므로 Admin/User 리팩토링에 영향을 받지 않아야 한다. 기존 Customer 인증이 중단되면 서비스 장애가 발생한다.

**독립 테스트**: Firebase UID를 가진 Customer 사용자를 생성하고, Firebase 인증 API로 로그인 후 JWT 토큰을 받아 API 요청이 성공함을 확인한다. Filament 패널 접근은 실패해야 한다.

**수락 시나리오**:

1. **Given** Customer가 Firebase로 인증됨, **When** Customer API에 JWT 토큰으로 요청, **Then** 요청이 성공하고 Customer 데이터가 반환됨
2. **Given** Customer 사용자가 firebase_uid를 보유함, **When** Customer가 Filament Admin 패널에 접근 시도, **Then** 접근이 거부되고 인증 실패 응답이 반환됨
3. **Given** Customer가 기존 Firebase 앱에서 로그인함, **When** 프로필 업데이트 요청, **Then** Laravel API가 Firebase 인증을 검증하고 업데이트를 처리함
4. **Given** 신규 Customer가 Firebase 회원가입 완료, **When** Laravel webhook이 사용자 생성 이벤트 수신, **Then** Customer 레코드가 users 테이블에 생성되고 firebase_uid가 저장됨

---

### 사용자 스토리 4 - Spatie Permissions 제거 및 경량화 (우선순위: P1)

Spatie Permissions 패키지를 제거하고, 자체 권한 모델(tenant_users 피벗 테이블)을 사용하여 M:N 관계를 관리한다. Policy 클래스는 제거하고, 간단한 권한 체크 메서드를 User 모델에 직접 구현한다.

**우선순위 이유**: Spatie Permissions는 현재 시스템 요구사항보다 과도하게 복잡하며, 멀티테넌시와 충돌하는 team_id 개념을 포함한다. 경량화를 통해 유지보수성과 성능을 개선한다.

**독립 테스트**: Spatie Permissions 의존성을 제거하고 composer update 후, 기존 권한 체크 로직이 새로운 tenant_users 기반 메서드로 대체되어 모든 테스트가 통과함을 확인한다.

**수락 시나리오**:

1. **Given** composer.json에서 spatie/laravel-permission이 제거됨, **When** composer update 실행, **Then** 의존성이 제거되고 오류 없이 설치 완료됨
2. **Given** User 모델에서 HasRoles trait이 제거됨, **When** 애플리케이션 부팅, **Then** 오류 없이 정상 작동함
3. **Given** tenant_users 테이블에 user_id, tenant_type, tenant_id, role 컬럼이 존재함, **When** Admin에게 새 역할 할당, **Then** tenant_users 레코드가 생성되고 canAccessTenant() 메서드가 true 반환
4. **Given** Policy 클래스들(OrganizationPolicy, BrandPolicy, StorePolicy)이 제거됨, **When** Filament 리소스에서 권한 체크, **Then** User 모델의 hasRoleForTenant() 메서드로 권한 검증이 수행됨

---

### 엣지 케이스

- **Admin이 특정 테넌트에서 역할을 잃은 경우**: Admin이 Organization A에서 제거되었지만 Organization B의 세션이 활성화된 상태에서 Organization A에 접근 시도하면 어떻게 되나요? → 접근 거부 및 테넌트 목록으로 리다이렉트
- **User에게 테넌트 역할이 잘못 할당된 경우**: User에게 실수로 Organization의 역할이 할당되면 어떻게 되나요? → User는 Organization 패널 자체에 접근할 수 없으므로 역할이 무의미함. canAccessPanel()에서 차단됨
- **Customer가 Admin 패널 URL을 직접 입력하는 경우**: Customer가 /admin 경로로 접근 시도하면? → Firebase UID만 있고 역할이 없으므로 canAccessPanel()이 false 반환, 로그인 페이지로 리다이렉트
- **역할 마이그레이션 중 충돌**: Spatie roles 테이블에서 새로운 tenant_users로 마이그레이션 시 role 이름이 중복되면? → 테넌트별로 역할을 분리하므로 (tenant_type, tenant_id, role) 조합이 고유함
- **테넌트 삭제 시 역할 정리**: Organization이 soft delete될 때 연관된 tenant_users 레코드는? → onDelete cascade로 자동 삭제 또는 soft delete 상태 확인 로직 추가 필요
- **동시 접속 세션 충돌**: Admin이 Organization A와 B를 다른 브라우저 탭에서 동시에 열면? → Filament는 세션 기반 테넌트 컨텍스트를 사용하므로 마지막 선택 테넌트가 전역 적용됨. 탭별 독립성은 지원되지 않음 (설계 제약)

## 요구사항 *(필수)*

### 기능 요구사항

- **FR-001**: 시스템은 반드시 세 가지 사용자 티어(Admin, User, Customer)를 구분해야 함
- **FR-002**: Admin은 반드시 여러 Organization, Brand, Store 테넌트에 동시 접근할 수 있어야 함
- **FR-003**: Admin은 반드시 각 테넌트에서 서로 다른 역할(owner, manager, viewer 등)을 보유할 수 있어야 함
- **FR-004**: User는 반드시 Platform 및 System 패널에만 접근 가능하며, 테넌트 패널 접근은 차단되어야 함
- **FR-005**: Customer는 반드시 Firebase 인증을 통해서만 인증되며, Filament Admin 패널 접근은 차단되어야 함
- **FR-006**: 시스템은 반드시 Spatie Permissions 패키지 의존성을 제거하고 자체 권한 모델을 사용해야 함
- **FR-007**: 권한 검증은 반드시 tenant_users 피벗 테이블을 기반으로 수행되어야 함 (user_id, tenant_type, tenant_id, role 조합)
- **FR-008**: 시스템은 반드시 기존 Policy 클래스들을 제거하고 User 모델의 메서드로 권한 체크를 단순화해야 함
- **FR-009**: 시스템은 반드시 Admin이 테넌트 선택 화면(Filament getTenants())에서 자신이 접근 가능한 모든 테넌트를 조회할 수 있어야 함
- **FR-010**: Admin이 특정 테넌트에 접근할 때 시스템은 반드시 해당 테넌트의 역할을 검증(canAccessTenant())해야 함
- **FR-011**: 시스템은 반드시 테넌트 컨텍스트 외부(글로벌 패널)에서 User의 platform_admin 또는 system_admin 역할을 검증해야 함
- **FR-012**: 시스템은 반드시 Customer의 Firebase UID 기반 인증 흐름을 유지하되, Admin/User 권한 모델과 독립적으로 작동해야 함
- **FR-013**: 마이그레이션 시 시스템은 반드시 기존 permission_tables 마이그레이션을 제거하고 새로운 tenant_users 테이블로 대체해야 함
- **FR-014**: 시스템은 반드시 User 모델에서 HasRoles trait을 제거하고 자체 관계 메서드를 구현해야 함
- **FR-015**: 테넌트 삭제 시 시스템은 반드시 연관된 tenant_users 레코드를 정리(cascade delete)해야 함

**Review Moai 원칙 고려사항**:
- 멀티테넌시: tenant_users 테이블에 tenant_type과 tenant_id로 폴리모픽 관계 구현, 각 테넌트별 데이터 격리 보장
- 관찰성: 권한 변경 작업(역할 할당/제거)에 Activity Log 기록, Critical 작업 시 Slack 알림 적용
- 드라이버 패턴: Firebase 인증과 Filament 인증을 독립적으로 유지, 향후 다른 인증 제공자 추가 시 확장 가능

### 주요 엔티티 *(기능이 데이터를 포함하는 경우 포함)*

- **User**: 시스템의 모든 사용자를 나타냄. user_type 컬럼(admin, user, customer)으로 티어 구분. Firebase UID, 역할 관계, 테넌트 관계 포함
- **TenantUser (피벗)**: Admin과 테넌트 간의 M:N 관계 및 역할 정보. 컬럼: user_id, tenant_type(ORG/BRD/STR), tenant_id, role(owner/manager/viewer)
- **Organization**: 최상위 테넌트 엔티티. Admin이 멀티테넌시로 접근 가능
- **Brand**: Organization 하위 테넌트. Admin이 Brand 패널에서 멀티테넌시로 접근
- **Store**: Brand 하위 테넌트. Admin이 Store 패널에서 멀티테넌시로 접근
- **GlobalRole**: Platform 및 System 스코프의 역할(platform_admin, system_admin). User 전용, 테넌트와 무관

## 성공 기준 *(필수)*

### 측정 가능한 결과

- **SC-001**: Admin 사용자가 5개 이상의 서로 다른 테넌트에 접근하고, 각 테넌트에서 역할에 맞는 권한으로 작업을 수행할 수 있음 (테스트 시나리오로 검증)
- **SC-002**: User 사용자가 Platform 또는 System 패널에 접근 가능하나, Organization/Brand/Store 패널 접근 시 100% 차단됨
- **SC-003**: Customer 사용자가 Firebase 인증 API를 통해 로그인하고 API 요청 시 95% 이상 성공률을 유지함 (기존 인증 흐름 유지)
- **SC-004**: Spatie Permissions 의존성 제거 후 composer 의존성 크기가 최소 15% 감소하고, 권한 체크 쿼리가 평균 2개 이하로 단순화됨
- **SC-005**: 모든 Policy 클래스 제거 후 코드베이스 LOC(Lines of Code)가 최소 300줄 감소함
- **SC-006**: 기존 권한 관련 테스트 케이스가 새로운 권한 모델로 마이그레이션되어 100% 통과함
- **SC-007**: 테넌트 권한 검증 성능이 기존 Spatie 기반 대비 50% 이상 개선됨 (DB 쿼리 수 감소)
- **SC-008**: Admin이 테넌트를 변경할 때 평균 응답 시간이 500ms 이하로 유지됨

## 비고

### 가정 사항

- Firebase 인증은 Customer 전용이며, Admin/User는 Filament의 기본 세션 기반 인증을 사용한다고 가정
- 테넌트 역할(owner, manager, viewer)은 시스템에서 사전 정의되며, 동적으로 생성되지 않는다고 가정
- 기존 Spatie roles 테이블의 데이터는 마이그레이션 스크립트로 tenant_users 테이블로 이관 가능하다고 가정
- Platform과 System은 단일 인스턴스 패널이며, 멀티테넌시를 적용하지 않는다고 가정
- Policy 제거 후 Filament 리소스에서 권한 체크는 User 모델의 메서드(예: canManageTenant())를 직접 호출한다고 가정
- 온보딩 위자드(/org/new, /store/new)는 기존 방식을 유지하며, 새 권한 모델에서도 작동해야 한다고 가정

### 범위 외

- Permission 레벨의 세밀한 권한 제어 (예: create-user, delete-post)는 구현하지 않음. 역할(Role) 기반 접근만 지원
- 역할의 계층 구조(Role Hierarchy) 지원하지 않음. 각 역할은 독립적이며 상속 관계 없음
- 동적 역할 생성 UI는 구현하지 않음. 역할은 시더(Seeder)로 사전 정의됨
- Customer의 세밀한 권한 관리는 Firebase Custom Claims에 위임하며, Laravel에서 관리하지 않음
- 역할 변경 이력 추적은 Activity Log 기본 기능으로만 제공하며, 별도 감사 테이블은 생성하지 않음

### 기술적 제약

- Filament Tenancy는 세션 기반 테넌트 컨텍스트를 사용하므로, 동일 사용자의 여러 브라우저 탭에서 서로 다른 테넌트를 독립적으로 유지할 수 없음 (마지막 선택 테넌트가 전역 적용됨)
- Laravel의 morphMap을 사용한 폴리모픽 관계는 테넌트 타입을 'ORG', 'BRD', 'STR' 문자열로 저장하며, 클래스명 변경 시 마이그레이션 필요
- Firebase 인증은 stateless이므로, Admin/User의 세션 기반 인증과 혼용 시 guard 설정에 주의 필요
- Spatie Permissions 제거 시 기존 코드에서 hasPermissionTo(), can() 호출이 있다면 모두 제거 또는 대체 필요
- 테넌트 soft delete 시 tenant_users 레코드가 cascade되지 않으므로, 별도 정리 로직(이벤트 리스너 또는 스케줄러) 필요
