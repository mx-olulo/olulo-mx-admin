---
id: I18N-001
version: 0.1.0
status: completed
created: 2025-10-19
updated: 2025-10-19
author: @user
priority: high
category: feature
labels:
  - i18n
  - filament
  - ui
  - localization
scope:
  packages:
    - app/Filament/Organization/Resources
  files:
    - OrganizationResource.php
    - OrganizationForm.php
    - OrganizationsTable.php
    - ListOrganizationActivities.php
---

# @SPEC:I18N-001: Filament 리소스 다국어 지원 시스템

## HISTORY

### v0.1.0 (2025-10-19)
- **COMPLETED**: TDD 구현 완료 (RED-GREEN-REFACTOR)
- **TESTS**: 20개 테스트 통과 (319 assertions)
  - OrganizationResourceI18nTest.php: 14 시나리오
  - TranslationCompletenessTest.php: 6 시나리오
- **COVERAGE**: 90개 번역 완료 (es-MX, en, ko)
  - organizations.resource: 3개 키
  - organizations.fields: 5개 키
  - organizations.columns: 6개 키
  - organizations.actions: 2개 키
  - organizations.activities: 14개 키
  - common.actions: 3개 키 (재사용)
- **QUALITY**: TRUST 5원칙 모두 충족
  - Test First: ✅ 20/20 passed (100%)
  - Readable: ✅ Pint 141 files PASS
  - Unified: ✅ 3개 언어 키 구조 일치 (TranslationCompletenessTest)
  - Secured: ✅ Laravel validation + XSS 방지
  - Trackable: ✅ TAG 체인 100% 연결 (@SPEC → @TEST → @CODE → @DOC)
- **FILES**: 12개 파일 수정 (1,648+ LOC)
  - 번역: lang/{es-MX,en,ko}/filament.php (3개)
  - PHP: OrganizationResource.php, OrganizationForm.php, OrganizationsTable.php, ListOrganizationActivities.php (4개)
  - 테스트: OrganizationResourceI18nTest.php, TranslationCompletenessTest.php (2개)
  - 문서: spec.md, plan.md, acceptance.md (3개)
- **COMMITS**: 3개 (fca8d51, 8a523bc, b78ddc2)
- **AUTHOR**: @user
- **RELATED**: [I18N-001-implementation.md](../../docs/living/I18N-001-implementation.md)

### v0.0.1 (2025-10-19)
- **INITIAL**: Filament 리소스 다국어 지원 명세 최초 작성
- **AUTHOR**: @user
- **SCOPE**: OrganizationResource의 모든 UI 요소 다국어화 (폼 필드, 테이블 컬럼, 액션, 활동 로그)
- **CONTEXT**: 멕시코 시장 진출을 위한 스페인어 우선 지원, 향후 다국가 확장 준비
- **LANGUAGES**: es-MX (기본), en, ko 지원

---

## Environment (환경)

### 시스템 환경
- **Framework**: Laravel 12.x
- **Admin Panel**: Filament 4.x
- **지원 언어**:
  - es-MX (스페인어-멕시코) - 기본 언어
  - en (영어) - 폴백 언어
  - ko (한국어) - 추가 지원
- **기본 로케일**: es-MX
- **폴백 로케일**: en
- **번역 시스템**: Laravel i18n (`__()` 헬퍼 함수)

### 전제 조건
- Laravel의 다국어 시스템 활성화 (`config/app.php`)
- Filament 패널 설정 완료 (Organization 패널 포함)
- OrganizationResource 기본 구현 완료
- `lang/` 디렉토리 존재 및 기본 구조 설정
- OrganizationForm, OrganizationsTable, ListOrganizationActivities 클래스 존재

### 기술적 제약
- **다국어 파일 위치**: 반드시 `lang/` 디렉토리 사용 (`resources/lang/` 금지)
- **파일 구조**: `lang/{locale}/filament.php` 확장
- **명명 규칙**: `filament.{resource}.{category}.{key}` 형식 (snake_case)
- **폴백 전략**: 현재 로케일 → en → 키 자체
- **성능**: 번역 파일 크기 1000개 키 이하 권장

---

## Assumptions (가정)

### 비즈니스 가정
1. 멕시코 시장이 주요 타겟이므로 스페인어(es-MX)가 기본 언어
2. 관리자 인터페이스도 다국어 지원 필요 (현지 직원 사용)
3. 향후 다른 리소스(Menu, Order 등)도 동일한 패턴으로 다국어화 예정
4. 번역 품질은 전문 번역가 검수 완료 가정

### 기술적 가정
1. Laravel의 `__()` 헬퍼 함수가 성능에 큰 영향을 주지 않음
2. Filament의 번역 시스템이 Laravel i18n과 완전히 호환됨
3. 번역 파일은 PHP 배열 형식 유지 (데이터베이스 저장 X)
4. 프로덕션 환경에서는 `php artisan config:cache` 실행하여 번역 캐싱 활성화

### 사용자 가정
1. 관리자는 언어 전환 기능을 사용할 수 있음 (Filament 패널 설정)
2. 번역 품질은 전문 번역가 검수 가정
3. UI 요소의 문맥(context)은 번역 키로 충분히 전달됨
4. 동적 콘텐츠(조직명 등)는 번역하지 않음

---

## Requirements (요구사항)

### Ubiquitous Requirements (필수 기능)

1. **다국어 파일 구조**
   - 시스템은 `lang/{locale}/filament.php` 파일에 OrganizationResource 번역을 제공해야 한다
   - 시스템은 es-MX, en, ko 3개 언어의 완전한 번역을 제공해야 한다
   - 시스템은 기존 dashboard 번역 구조를 유지하면서 organizations 섹션을 추가해야 한다

2. **폼 필드 다국어화**
   - 시스템은 5개 폼 필드(name, description, contact_email, contact_phone, is_active)의 라벨을 다국어로 표시해야 한다
   - 시스템은 `OrganizationForm.php`에서 `__('filament.organizations.fields.{field}')` 패턴을 사용해야 한다
   - 시스템은 필드 헬퍼 텍스트와 플레이스홀더도 다국어로 제공해야 한다

3. **테이블 컬럼 다국어화**
   - 시스템은 6개 테이블 컬럼(name, contact_email, contact_phone, is_active, created_at, updated_at)의 라벨을 다국어로 표시해야 한다
   - 시스템은 `OrganizationsTable.php`에서 `__('filament.organizations.columns.{column}')` 패턴을 사용해야 한다

4. **액션 라벨 다국어화**
   - 시스템은 5개 액션(view, edit, delete, activities, back)의 라벨을 다국어로 표시해야 한다
   - 시스템은 공통 액션(view, edit, delete)은 `common.actions.*`에서 재사용해야 한다
   - 시스템은 커스텀 액션(activities, back)은 `organizations.actions.*`에서 정의해야 한다

5. **활동 로그 다국어화**
   - 시스템은 활동 로그 페이지의 제목, 컬럼, 필터, 이벤트 타입을 다국어로 표시해야 한다
   - 시스템은 동적 제목에 조직명을 포함할 때 번역을 적용해야 한다 (`Activity Log: {name}`)
   - 시스템은 이벤트 타입(created, updated, deleted)을 다국어로 표시해야 한다

6. **리소스 네비게이션 다국어화**
   - 시스템은 OrganizationResource의 네비게이션 라벨을 다국어로 표시해야 한다
   - 시스템은 모델 라벨(단수/복수)을 다국어로 제공해야 한다
   - 시스템은 페이지 제목(List, Create, Edit, View)을 다국어로 표시해야 한다

### Event-driven Requirements (이벤트 기반)

1. **로케일 변경 이벤트**
   - WHEN 사용자가 로케일을 변경하면, 시스템은 모든 UI 요소를 선택된 언어로 즉시 표시해야 한다
   - WHEN 로케일 변경이 발생하면, 시스템은 페이지 새로고침 없이 UI를 업데이트해야 한다

2. **번역 키 누락 이벤트**
   - WHEN 번역 키가 현재 로케일에 없으면, 시스템은 영어(en) 번역을 사용해야 한다
   - WHEN 영어 번역도 없으면, 시스템은 번역 키 자체를 표시해야 한다 (디버깅 용이)
   - WHEN 개발 환경에서 누락된 키를 발견하면, 시스템은 로그에 경고를 기록해야 한다

3. **새 리소스 추가 이벤트**
   - WHEN 새 Filament 리소스가 추가되면, 시스템은 동일한 번역 패턴을 적용할 수 있어야 한다
   - WHEN 번역 파일에 새 섹션을 추가할 때, 시스템은 기존 구조를 깨지 않아야 한다

### State-driven Requirements (상태 기반)

1. **폼 작성 중 상태**
   - WHILE 사용자가 조직 생성/수정 폼을 작성할 때, 시스템은 필드 라벨을 현재 로케일로 표시해야 한다
   - WHILE 폼 검증 오류가 발생할 때, 시스템은 오류 메시지를 현재 로케일로 표시해야 한다
   - WHILE 폼 헬퍼 텍스트를 표시할 때, 시스템은 현재 로케일로 표시해야 한다

2. **테이블 조회 중 상태**
   - WHILE 사용자가 조직 목록을 조회할 때, 시스템은 컬럼명과 액션명을 현재 로케일로 표시해야 한다
   - WHILE 테이블 필터를 사용할 때, 시스템은 필터 옵션을 현재 로케일로 표시해야 한다
   - WHILE 테이블이 비어있을 때, 시스템은 "데이터 없음" 메시지를 현재 로케일로 표시해야 한다

3. **활동 로그 조회 중 상태**
   - WHILE 사용자가 활동 로그를 조회할 때, 시스템은 이벤트 타입(created, updated, deleted)을 현재 로케일로 표시해야 한다
   - WHILE 활동 로그를 필터링할 때, 시스템은 필터 라벨과 옵션을 현재 로케일로 표시해야 한다

### Optional Features (선택적 기능)

1. **번역 수정**
   - WHERE 관리자가 번역을 수정하려면, 시스템은 `lang/{locale}/filament.php` 파일을 직접 편집할 수 있어야 한다
   - WHERE 번역 수정 후, 시스템은 캐시 클리어 없이 즉시 반영할 수 있다 (개발 환경)

2. **새 언어 추가**
   - WHERE 향후 새 언어(예: pt-BR)를 추가하려면, 시스템은 `lang/{new_locale}/filament.php` 파일을 생성하면 자동으로 지원해야 한다
   - WHERE 새 언어 추가 시, 시스템은 코드 변경 없이 번역 파일만 추가하면 동작해야 한다

3. **번역 품질 검증**
   - WHERE 번역 품질을 검증하려면, 시스템은 누락된 번역 키를 감지할 수 있어야 한다
   - WHERE 번역 일관성을 검증하려면, 시스템은 동일한 키가 여러 언어에서 누락되지 않았는지 확인할 수 있어야 한다

### Constraints (제약사항)

1. **다국어 파일 위치**
   - IF 다국어 파일을 생성할 때, 시스템은 반드시 `lang/` 디렉토리 하위에 생성해야 한다
   - IF `resources/lang/` 디렉토리를 사용하면, 시스템은 Laravel과 충돌이 발생한다 (금지)

2. **번역 키 명명 규칙**
   - IF 번역 키를 정의할 때, 시스템은 `filament.{resource}.{category}.{key}` 형식을 따라야 한다
   - IF 번역 키에 snake_case를 사용하지 않으면, 시스템은 일관성이 깨진다 (금지)
   - IF 카테고리는 resource, fields, columns, actions, activities 중 하나여야 한다

3. **폴백 전략**
   - IF 현재 로케일 번역이 없으면, 시스템은 반드시 영어(en)를 폴백으로 사용해야 한다
   - IF 영어 번역도 없으면, 시스템은 번역 키 자체를 표시해야 한다 (숨기지 않음)

4. **성능 제약**
   - IF 번역 파일이 너무 크면, 시스템은 로딩 시간이 증가할 수 있다 (1000개 키 이하 권장)
   - IF 번역 캐싱을 사용하면, 시스템은 프로덕션 환경에서 `php artisan config:cache` 실행이 필요하다

5. **확장성 제약**
   - IF 모든 리소스를 한 파일에 번역하면, 시스템은 유지보수가 어려워진다 (파일 분리 권장)
   - IF 향후 100개 이상 리소스가 추가되면, 시스템은 `lang/{locale}/filament/{resource}.php` 구조로 분리해야 한다

---

## Traceability (@TAG)

- **SPEC**: @SPEC:I18N-001
- **TEST**:
  - tests/Feature/Filament/OrganizationResourceI18nTest.php
  - tests/Feature/I18n/TranslationCompletenessTest.php
- **CODE**:
  - app/Filament/Organization/Resources/Organizations/OrganizationResource.php
  - app/Filament/Organization/Resources/Organizations/Schemas/OrganizationForm.php
  - app/Filament/Organization/Resources/Organizations/Tables/OrganizationsTable.php
  - app/Filament/Organization/Resources/Organizations/Pages/ListOrganizationActivities.php
  - lang/es-MX/filament.php
  - lang/en/filament.php
  - lang/ko/filament.php
- **DOC**: docs/development/i18n-guide.md (선택)

---

## Implementation Notes

### 번역 파일 구조 예시

**lang/es-MX/filament.php**:
```php
return [
    'dashboard' => [
        // 기존 대시보드 번역 유지
    ],

    'organizations' => [
        'resource' => [
            'label' => 'Organización',
            'plural_label' => 'Organizaciones',
            'navigation_label' => 'Organizaciones',
        ],
        'fields' => [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'contact_email' => 'Correo Electrónico de Contacto',
            'contact_phone' => 'Teléfono de Contacto',
            'is_active' => 'Estado Activo',
        ],
        'columns' => [
            'name' => 'Nombre',
            'contact_email' => 'Correo Electrónico',
            'contact_phone' => 'Teléfono',
            'is_active' => 'Activo',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Fecha de Actualización',
        ],
        'actions' => [
            'activities' => 'Registro de Actividades',
            'back' => 'Volver',
        ],
        'activities' => [
            'title' => 'Registro de Actividades: {name}',
            'event_types' => [
                'created' => 'Creado',
                'updated' => 'Actualizado',
                'deleted' => 'Eliminado',
            ],
            'filters' => [
                'event_type' => 'Tipo de Evento',
            ],
        ],
    ],

    'common' => [
        'actions' => [
            'view' => 'Ver',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
        ],
    ],
];
```

### 코드 적용 예시

**OrganizationForm.php**:
```php
TextInput::make('name')
    ->label(__('filament.organizations.fields.name'))
    ->required()
    ->maxLength(255),

TextInput::make('description')
    ->label(__('filament.organizations.fields.description'))
    ->maxLength(500),
```

**OrganizationsTable.php**:
```php
TextColumn::make('name')
    ->label(__('filament.organizations.columns.name'))
    ->searchable()
    ->sortable(),

TextColumn::make('contact_email')
    ->label(__('filament.organizations.columns.contact_email'))
    ->searchable(),
```

**ListOrganizationActivities.php**:
```php
public function getTitle(): string
{
    return __('filament.organizations.activities.title', [
        'name' => $this->record->name
    ]);
}
```

---

## Acceptance Criteria

구체적인 테스트 시나리오는 `acceptance.md` 참조.

### 최소 검증 항목
1. 모든 폼 필드 라벨이 3개 언어로 표시됨
2. 모든 테이블 컬럼이 3개 언어로 표시됨
3. 모든 액션 라벨이 3개 언어로 표시됨
4. 활동 로그 페이지가 3개 언어로 표시됨
5. 로케일 변경 시 즉시 UI 업데이트
6. 누락된 번역 키가 폴백 전략에 따라 표시됨
7. 새 언어 추가 시 코드 변경 없이 동작
