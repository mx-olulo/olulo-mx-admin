# Roles and Permissions

본 문서는 롤/권한 체계의 최소 설계를 설명합니다. 코드와 주석은 영어, 문서는 한국어로 작성합니다.

## 롤 분류 (Taxonomy)
- admin
- org_admin
- store_owner
- store_manager
- staff
- customer

고객앱 이용자는 기본적으로 `customer` 롤을 부여받습니다. 관리자 패널(Filament)은 `admin | org_admin | store_owner | store_manager | staff`만 접근 가능합니다.

## 시스템 연동
- User 모델: `Spatie\Permission\Traits\HasRoles` 적용
- 시더: `DatabaseSeeder`에서 기본 롤 생성 및 샘플 유저에 롤 할당
- 로그인 플로우: Firebase 로그인 성공 시 `customer` 롤 자동 부여 (미보유 시)

## 접근 제어 원칙
- 관리자 패널(Filament): 운영/관리 롤만 접근 (customer 제외)
- 고객 API/화면: web 세션 인증 + customer 롤 보유 여부 기반(필요 시)
- 정책(Policies): 차기 단계에서 Organization/Store 단위 권한 세분화

## 추후 확장
- 조직/매장 단위 권한(organization_managers, store_managers) 도입
- 권한(Permissions) 세분화 후 롤에 매핑
- 활동 로그 및 감사지원
