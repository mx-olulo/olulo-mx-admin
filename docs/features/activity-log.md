# Activity Log (활동 로그)

## 개요

Activity Log는 시스템 내 핵심 모델의 변경 이력을 추적하는 기능입니다. Spatie Laravel Activity Log 패키지를 기반으로 구현되었으며, 멀티테넌시 환경에서 안전하게 작동하도록 설계되었습니다.

## 아키텍처

### 패키지

- **spatie/laravel-activitylog**: Laravel 모델의 변경 이력 자동 추적
- 설정 파일: `config/activitylog.php`
- 마이그레이션: `database/migrations/2025_10_11_073729_create_activity_log_table.php`

### 추적 대상 모델

다음 모델의 변경 사항이 자동으로 로깅됩니다:

| 모델 | 추적 속성 | Log Name |
|------|----------|----------|
| User | name, email, phone_number, locale, email_verified_at | user |
| Organization | name, description, contact_email, contact_phone, is_active | organization |
| Brand | organization_id, name, description, is_active | brand |
| Store | brand_id, organization_id, name, description, address, phone, is_active | store |
| Role | name, guard_name, team_id, scope_type, scope_ref_id | role |

### 로깅 정책

모든 모델은 다음 정책을 따릅니다:

- **logOnlyDirty()**: 실제 변경된 속성만 기록 (성능 최적화)
- **이벤트**: created, updated, deleted
- **Causer**: 변경을 수행한 사용자 자동 기록

### 제외 속성

User 모델의 경우 다음 속성은 로깅하지 않습니다:
- `last_login_at`: 자주 변경되어 로그 과부하 방지
- `remember_token`: 민감 정보 보호

## Filament 통합

### Organization Resource Activity Log

Organization 리소스에 Activity Log 조회 페이지가 추가되었습니다.

**접근 경로**: `/org/{organization}/activities`

**권한**:
- `view-activities` 권한 필요
- Organization 소유권 확인 (OrganizationPolicy)

**기능**:
- 이벤트 필터링 (created, updated, deleted)
- 변경 전후 값 표시
- 변경 사용자 표시
- 시간순 정렬

### 테이블 구조

| 컬럼 | 설명 |
|------|------|
| Event | 이벤트 타입 (created, updated, deleted) |
| User | 변경을 수행한 사용자 (causer) |
| Changes | 변경된 속성과 전후 값 |
| Date | 변경 시각 |

### N+1 쿼리 방지

Activity Log 조회 시 `causer` 관계를 eager loading하여 N+1 쿼리를 방지합니다.

```php
Activity::query()
    ->with('causer') // N+1 방지
    ->where('subject_type', Organization::class)
    ->where('subject_id', $organization->id)
```

## 보안

### 권한 체크

Activity Log 접근은 3-layer 권한 체계를 따릅니다:

1. **Gate::before**: PLATFORM/SYSTEM 스코프 자동 허용
2. **Spatie Permission**: `view-activities` 권한 체크
3. **Filament Tenant**: Organization 소유권 체크

### 테넌트 격리

각 Organization은 자신의 Activity Log만 조회 가능합니다. 다른 Organization의 로그는 접근 불가합니다.

```php
// OrganizationPolicy::canAccessOrganization()
if ($tenant->scope_type === ScopeType::ORGANIZATION->value) {
    return $tenant->scope_ref_id === $organization->id;
}
```

## 성능 최적화

### 1. logOnlyDirty()

실제 변경된 속성만 기록하여 불필요한 DB INSERT 방지:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'email', ...])
        ->logOnlyDirty() // 변경 사항만 기록
}
```

### 2. Eager Loading

N+1 쿼리 방지를 위해 causer 관계를 사전 로드:

```php
->with('causer')
```

### 3. 인덱스

Activity Log 테이블은 다음 인덱스를 가집니다:
- `(subject_type, subject_id)`: 특정 모델의 로그 조회
- `(causer_id, causer_type)`: 사용자별 활동 조회

## 데이터 보존 정책

**기본 설정**: 365일

```php
// config/activitylog.php
'delete_records_older_than_days' => 365,
```

**권장 사항**:
- Production: 730일 (2년)
- Staging: 90일
- Local: 30일

환경변수 설정:
```env
ACTIVITY_LOG_RETENTION_DAYS=730
```

## 비동기 처리 (선택 사항)

대용량 트래픽 환경에서는 Activity Log를 Queue로 처리할 수 있습니다:

```php
// config/activitylog.php
'queue' => env('ACTIVITY_LOG_QUEUE_ENABLED', false),
'queue_name' => env('ACTIVITY_LOG_QUEUE_NAME', 'default'),
```

**적용 시기**: 프로덕션 배포 후 성능 모니터링 결과에 따라 결정

## 사용 예시

### 모델에 Activity Log 추가

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class YourModel extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status']) // 추적할 속성
            ->logOnlyDirty() // 변경 사항만
            ->useLogName('your-model'); // 로그 이름
    }
}
```

### Activity Log 조회

```php
// 특정 모델의 모든 활동
$activities = Activity::forSubject($organization)->get();

// 특정 사용자의 모든 활동
$activities = Activity::causedBy($user)->get();

// 특정 이벤트만 조회
$activities = Activity::where('description', 'updated')->get();
```

## 테스트

Activity Log 기능은 다음 테스트로 검증됩니다:

**파일**: `tests/Feature/ActivityLogTest.php`

**커버리지**:
- Organization 생성 로깅
- Organization 수정 로깅 (dirty attributes)
- Organization 삭제 로깅
- 인증된 사용자의 causer 추적
- 속성별 변경 추적

**실행**:
```bash
php artisan test --filter=ActivityLogTest
```

## 향후 계획

### 단기
- [ ] Brand, Store 리소스에도 Activity Log 페이지 추가
- [ ] Activity Log 전용 관리 페이지 생성 (전체 시스템 활동 조회)
- [ ] 사용자별 활동 이력 조회 기능

### 중기
- [ ] Activity Log 데이터 정리 스케줄러 설정
- [ ] 성능 모니터링 및 Queue 기반 처리 검토
- [ ] Activity Log 통계 및 대시보드

## 참조

- [Spatie Laravel Activity Log 공식 문서](https://spatie.be/docs/laravel-activitylog/)
- `config/activitylog.php`: 설정 파일
- `app/Policies/OrganizationPolicy.php`: 권한 정책
- `docs/security/authorization.md`: 3-layer 권한 체계
