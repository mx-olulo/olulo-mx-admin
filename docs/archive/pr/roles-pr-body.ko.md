# 변경 사항

- **[역할 시스템 연동]**
  - `app/Models/User.php`: Spatie `HasRoles` 트레이트 적용
  - Filament 접근 제한: `UserRole::panelAccess()` 보유 롤만 접근 가능
- **[Enum 도입]**
  - `app/Enums/UserRole.php`: 시스템 역할 단일 출처
  - `toArray()`, `panelAccess()` 제공
- **[시더 정비]**
  - `database/seeders/DatabaseSeeder.php`
    - 기본 롤은 `UserRole::toArray()`로 생성
    - 샘플 관리자 유저는 `local` 환경에서만 생성 및 `ADMIN` 롤 부여
- **[인증 플로우 보완]**
  - `app/Http/Controllers/Customer/AuthController.php`: Firebase 로그인 성공 시 `CUSTOMER` 롤 자동 부여(미보유 시)
- **[문서 추가]**
  - `docs/roles-and-permissions.md`
  - `docs/organizations.md`

# 마이그레이션/시드
- Spatie permission 테이블 필요 시 `php artisan migrate`
- 로컬 개발에서 기본 롤/샘플 유저 확인: `php artisan db:seed`
- 운영 환경에서는 샘플 유저는 생성되지 않음(별도 CLI로 생성 필요)

# 영향 범위
- 관리자 패널 접근 권한이 롤 기반으로 제한됨(`customer`는 접근 불가)
- 로그인 시 고객 역할 자동 부여로 고객 영역 권한 판별이 명확해짐

# 후속 작업(별도 PR)
- `OrganizationPolicy` 및 Filament Resource 권한 연동
- Stores/Managers/Invitations 도입 시 역할/정책 세분화
- Public API(organizations)는 소비자 정의 후 설계

# 테스트 방법
- 로컬에서 마이그레이션 및 시드 실행 후, Filament 접근 권한 확인
- Firebase 로그인 → 세션 확립 후 사용자에 `customer` 롤 부여 확인
