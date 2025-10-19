# SPEC-I18N-001 수락 기준

> Filament 리소스 다국어 지원 시스템 테스트 시나리오

---

## 📋 개요

본 문서는 SPEC-I18N-001의 구현 완료를 검증하기 위한 상세한 테스트 시나리오를 정의한다. 모든 시나리오는 Given-When-Then 형식을 따르며, 실제 Feature Test 코드로 구현 가능해야 한다.

---

## 🧪 테스트 시나리오

### 시나리오 1: 스페인어(es-MX) UI 표시

**카테고리**: 다국어 폼 필드
**우선순위**: Critical
**테스트 파일**: `tests/Feature/Filament/OrganizationResourceI18nTest.php`

#### Given-When-Then

**Given**:
- 사용자가 Organization 패널에 로그인되어 있다
- 애플리케이션 로케일이 `es-MX`로 설정되어 있다
- OrganizationResource가 존재한다

**When**:
- 사용자가 조직 생성 페이지(`/organization/organizations/create`)로 이동한다

**Then**:
- 폼 필드 라벨이 스페인어로 표시되어야 한다:
  - `name` 필드: "Nombre"
  - `description` 필드: "Descripción"
  - `contact_email` 필드: "Correo Electrónico de Contacto"
  - `contact_phone` 필드: "Teléfono de Contacto"
  - `is_active` 필드: "Estado Activo"

**검증 방법**:
```php
test('조직 생성 폼이 스페인어로 표시됨', function () {
    // Given
    $user = User::factory()->create();
    $this->actingAs($user);
    app()->setLocale('es-MX');

    // When
    Livewire::test(CreateOrganization::class)
        // Then
        ->assertSee('Nombre')
        ->assertSee('Descripción')
        ->assertSee('Correo Electrónico de Contacto')
        ->assertSee('Teléfono de Contacto')
        ->assertSee('Estado Activo');
});
```

---

### 시나리오 2: 영어(en) 폴백 전략

**카테고리**: 번역 키 폴백
**우선순위**: High
**테스트 파일**: `tests/Feature/I18n/TranslationFallbackTest.php`

#### Given-When-Then

**Given**:
- 애플리케이션 로케일이 `fr`(프랑스어, 미지원 언어)로 설정되어 있다
- `lang/fr/filament.php` 파일이 존재하지 않는다
- 폴백 로케일이 `en`로 설정되어 있다

**When**:
- 사용자가 조직 목록 페이지로 이동한다

**Then**:
- 테이블 컬럼이 영어로 표시되어야 한다:
  - `name` 컬럼: "Name"
  - `contact_email` 컬럼: "Contact Email"
  - `is_active` 컬럼: "Active"

**검증 방법**:
```php
test('미지원 언어 사용 시 영어로 폴백됨', function () {
    // Given
    $user = User::factory()->create();
    $this->actingAs($user);
    app()->setLocale('fr'); // 미지원 언어
    config(['app.fallback_locale' => 'en']);

    // When
    Livewire::test(ListOrganizations::class)
        // Then
        ->assertSee('Name')
        ->assertSee('Contact Email')
        ->assertSee('Active');
});
```

---

### 시나리오 3: 한국어(ko) 활동 로그 표시

**카테고리**: 다국어 활동 로그
**우선순위**: High
**테스트 파일**: `tests/Feature/Filament/OrganizationActivityI18nTest.php`

#### Given-When-Then

**Given**:
- 애플리케이션 로케일이 `ko`로 설정되어 있다
- 조직 "Test Organization"이 데이터베이스에 존재한다
- 해당 조직에 활동 로그(created, updated)가 3개 존재한다

**When**:
- 사용자가 조직 활동 로그 페이지로 이동한다

**Then**:
- 페이지 제목이 한국어로 표시되어야 한다: "활동 로그: Test Organization"
- 이벤트 타입이 한국어로 표시되어야 한다:
  - `created`: "생성됨"
  - `updated`: "수정됨"
  - `deleted`: "삭제됨"
- 필터 라벨이 한국어로 표시되어야 한다: "이벤트 유형"

**검증 방법**:
```php
test('활동 로그 페이지가 한국어로 표시됨', function () {
    // Given
    $user = User::factory()->create();
    $org = Organization::factory()->create(['name' => 'Test Organization']);
    activity()->performedOn($org)->event('created')->log('생성');
    activity()->performedOn($org)->event('updated')->log('수정');

    $this->actingAs($user);
    app()->setLocale('ko');

    // When
    Livewire::test(ListOrganizationActivities::class, ['record' => $org->id])
        // Then
        ->assertSeeHtml('활동 로그: Test Organization')
        ->assertSee('생성됨')
        ->assertSee('수정됨')
        ->assertSee('이벤트 유형');
});
```

---

### 시나리오 4: 로케일 변경 시 즉시 UI 업데이트

**카테고리**: 동적 언어 전환
**우선순위**: Medium
**테스트 파일**: `tests/Feature/Filament/LocaleSwitchingTest.php`

#### Given-When-Then

**Given**:
- 사용자가 조직 목록 페이지를 스페인어(es-MX)로 조회하고 있다
- 페이지에 조직 데이터 3개가 표시되어 있다

**When**:
- 사용자가 로케일을 영어(en)로 변경한다

**Then**:
- 페이지 새로고침 없이 모든 UI 요소가 영어로 즉시 업데이트되어야 한다
- 테이블 컬럼명이 영어로 변경되어야 한다:
  - "Nombre" → "Name"
  - "Correo Electrónico" → "Contact Email"
  - "Activo" → "Active"
- 액션 라벨이 영어로 변경되어야 한다:
  - "Ver" → "View"
  - "Editar" → "Edit"
  - "Eliminar" → "Delete"

**검증 방법**:
```php
test('로케일 변경 시 UI가 즉시 업데이트됨', function () {
    // Given
    $user = User::factory()->create();
    $this->actingAs($user);
    Organization::factory()->count(3)->create();

    // 초기 로케일: es-MX
    app()->setLocale('es-MX');
    $component = Livewire::test(ListOrganizations::class)
        ->assertSee('Nombre')
        ->assertSee('Correo Electrónico');

    // When: 로케일을 en으로 변경
    $component->call('setLocale', 'en');

    // Then: UI가 영어로 즉시 업데이트됨
    $component
        ->assertSee('Name')
        ->assertSee('Contact Email')
        ->assertSee('View')
        ->assertSee('Edit')
        ->assertDontSee('Nombre');
});
```

---

### 시나리오 5: 번역 키 완전성 검증

**카테고리**: 번역 품질 검증
**우선순위**: High
**테스트 파일**: `tests/Feature/I18n/TranslationCompletenessTest.php`

#### Given-When-Then

**Given**:
- `lang/es-MX/filament.php` 파일이 존재한다
- `lang/en/filament.php` 파일이 존재한다
- `lang/ko/filament.php` 파일이 존재한다
- OrganizationResource의 필수 번역 키 목록이 정의되어 있다:
  - `filament.organizations.resource.label`
  - `filament.organizations.resource.plural_label`
  - `filament.organizations.fields.name`
  - `filament.organizations.fields.description`
  - `filament.organizations.columns.name`
  - `filament.organizations.activities.title`
  - (총 30개 키)

**When**:
- 번역 완전성 검증 테스트를 실행한다

**Then**:
- 모든 필수 번역 키가 3개 언어 모두에 존재해야 한다
- 누락된 번역 키가 0개여야 한다
- 각 번역 키의 값이 빈 문자열이 아니어야 한다

**검증 방법**:
```php
test('모든 필수 번역 키가 3개 언어로 존재함', function () {
    // Given: 필수 번역 키 목록
    $requiredKeys = [
        'filament.organizations.resource.label',
        'filament.organizations.resource.plural_label',
        'filament.organizations.resource.navigation_label',
        'filament.organizations.fields.name',
        'filament.organizations.fields.description',
        'filament.organizations.fields.contact_email',
        'filament.organizations.fields.contact_phone',
        'filament.organizations.fields.is_active',
        'filament.organizations.columns.name',
        'filament.organizations.columns.contact_email',
        'filament.organizations.columns.contact_phone',
        'filament.organizations.columns.is_active',
        'filament.organizations.columns.created_at',
        'filament.organizations.columns.updated_at',
        'filament.organizations.actions.activities',
        'filament.organizations.actions.back',
        'filament.organizations.activities.title',
        'filament.organizations.activities.event_types.created',
        'filament.organizations.activities.event_types.updated',
        'filament.organizations.activities.event_types.deleted',
        'filament.common.actions.view',
        'filament.common.actions.edit',
        'filament.common.actions.delete',
    ];

    $locales = ['es-MX', 'en', 'ko'];

    // When & Then: 각 로케일별 번역 키 존재 확인
    foreach ($locales as $locale) {
        foreach ($requiredKeys as $key) {
            $translation = __($key, [], $locale);

            // 번역이 존재해야 함 (키 자체가 반환되지 않음)
            expect($translation)->not->toBe($key)
                ->and($translation)->not->toBeEmpty()
                ->and($translation)->toBeString();
        }
    }
});
```

---

### 시나리오 6: 공통 액션 재사용 검증

**카테고리**: 코드 재사용성
**우선순위**: Medium
**테스트 파일**: `tests/Feature/I18n/CommonActionsTest.php`

#### Given-When-Then

**Given**:
- `filament.common.actions.view`, `edit`, `delete` 번역 키가 정의되어 있다
- OrganizationsTable에서 이러한 공통 액션을 사용한다
- 애플리케이션 로케일이 `es-MX`로 설정되어 있다

**When**:
- 사용자가 조직 목록 페이지를 조회한다

**Then**:
- 공통 액션 라벨이 스페인어로 표시되어야 한다:
  - View: "Ver" (`common.actions.view`)
  - Edit: "Editar" (`common.actions.edit`)
  - Delete: "Eliminar" (`common.actions.delete`)
- 커스텀 액션도 정상 표시되어야 한다:
  - Activities: "Registro de Actividades" (`organizations.actions.activities`)

**검증 방법**:
```php
test('공통 액션이 재사용되고 정상 표시됨', function () {
    // Given
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $this->actingAs($user);
    app()->setLocale('es-MX');

    // When
    Livewire::test(ListOrganizations::class)
        // Then: 공통 액션
        ->assertSee('Ver')           // common.actions.view
        ->assertSee('Editar')        // common.actions.edit
        ->assertSee('Eliminar')      // common.actions.delete
        // 커스텀 액션
        ->assertSee('Registro de Actividades'); // organizations.actions.activities
});
```

---

### 시나리오 7: 새 언어 추가 시 코드 변경 없이 동작

**카테고리**: 확장성 검증
**우선순위**: Low
**테스트 파일**: `tests/Feature/I18n/NewLanguageTest.php`

#### Given-When-Then

**Given**:
- 기존 코드가 하드코딩된 로케일을 참조하지 않는다
- `lang/pt-BR/filament.php` 파일을 새로 생성한다 (포르투갈어-브라질)
- 모든 필수 번역 키가 포르투갈어로 작성되어 있다

**When**:
- 애플리케이션 로케일을 `pt-BR`로 설정한다
- 사용자가 조직 목록 페이지를 조회한다

**Then**:
- 코드 수정 없이 UI가 포르투갈어로 표시되어야 한다
- 테이블 컬럼명이 포르투갈어로 표시되어야 한다
- 액션 라벨이 포르투갈어로 표시되어야 한다

**검증 방법**:
```php
test('새 언어 추가 시 코드 변경 없이 동작함', function () {
    // Given: pt-BR 번역 파일 생성 (테스트 셋업에서 수행)
    // 실제 파일: lang/pt-BR/filament.php
    // Given
    $user = User::factory()->create();
    Organization::factory()->create();
    $this->actingAs($user);

    // When: pt-BR 로케일 설정
    app()->setLocale('pt-BR');

    // Then: 포르투갈어로 표시됨 (코드 변경 없음)
    Livewire::test(ListOrganizations::class)
        ->assertSee('Nome')                     // Name
        ->assertSee('E-mail de Contato')        // Contact Email
        ->assertSee('Ativo')                    // Active
        ->assertSee('Visualizar')               // View
        ->assertSee('Editar')                   // Edit
        ->assertSee('Excluir');                 // Delete
});
```

---

## ✅ Definition of Done (완료 조건)

### 필수 조건 (모두 충족 필요)

1. **번역 파일 생성**
   - [ ] `lang/es-MX/filament.php` 생성 완료 (30개 키)
   - [ ] `lang/en/filament.php` 생성 완료 (30개 키)
   - [ ] `lang/ko/filament.php` 생성 완료 (30개 키)
   - [ ] 모든 번역 키가 3개 언어로 존재

2. **코드 적용**
   - [ ] OrganizationForm.php에 번역 키 적용 (5개 필드)
   - [ ] OrganizationsTable.php에 번역 키 적용 (6개 컬럼)
   - [ ] ListOrganizationActivities.php에 번역 키 적용
   - [ ] OrganizationResource.php에 번역 키 적용
   - [ ] 하드코딩된 문자열 0개

3. **테스트 통과**
   - [ ] 시나리오 1: 스페인어 UI 표시 (PASS)
   - [ ] 시나리오 2: 영어 폴백 전략 (PASS)
   - [ ] 시나리오 3: 한국어 활동 로그 (PASS)
   - [ ] 시나리오 4: 로케일 변경 (PASS)
   - [ ] 시나리오 5: 번역 키 완전성 (PASS)
   - [ ] 시나리오 6: 공통 액션 재사용 (PASS)
   - [ ] 시나리오 7: 새 언어 추가 (PASS)
   - [ ] 테스트 커버리지 85% 이상

4. **품질 게이트**
   - [ ] TRUST 5원칙 준수 확인
   - [ ] @TAG 체인 무결성 검증
   - [ ] 린터 검증 통과 (Pint, PHPStan)
   - [ ] 기존 기능 회귀 테스트 통과

5. **문서화**
   - [ ] `plan.md` 작성 완료
   - [ ] `acceptance.md` 작성 완료
   - [ ] `i18n-guide.md` 작성 (선택)

---

## 🔍 수동 테스트 체크리스트

자동화 테스트 외에 다음 항목을 수동으로 검증하세요:

### UI 검증
- [ ] 스페인어 UI가 자연스럽게 표시됨 (네이티브 확인)
- [ ] 영어 UI가 자연스럽게 표시됨
- [ ] 한국어 UI가 자연스럽게 표시됨
- [ ] 레이아웃이 깨지지 않음 (긴 번역 텍스트)
- [ ] 모바일 반응형 UI 정상 동작

### 기능 검증
- [ ] 조직 생성 폼 정상 동작
- [ ] 조직 수정 폼 정상 동작
- [ ] 조직 목록 테이블 정상 동작
- [ ] 활동 로그 페이지 정상 동작
- [ ] 검색/필터 기능 정상 동작
- [ ] 액션(View, Edit, Delete) 정상 동작

### 성능 검증
- [ ] 페이지 로딩 시간 5% 이내 증가
- [ ] 번역 캐싱 정상 동작 (프로덕션)
- [ ] 메모리 사용량 정상 범위

---

## 📊 테스트 커버리지 목표

### 파일별 목표
- **OrganizationForm.php**: 90% 이상
- **OrganizationsTable.php**: 90% 이상
- **ListOrganizationActivities.php**: 85% 이상
- **OrganizationResource.php**: 85% 이상
- **번역 파일**: 100% (TranslationCompletenessTest)

### 전체 목표
- **라인 커버리지**: 85% 이상
- **브랜치 커버리지**: 80% 이상
- **함수 커버리지**: 90% 이상

---

**작성일**: 2025-10-19
**작성자**: @Alfred (spec-builder)
**관련 SPEC**: @SPEC:I18N-001
