# SPEC-I18N-001 번역 목록

> **Filament OrganizationResource 다국어 번역**
> **지원 언어**: es-MX (주), en (폴백), ko (추가)

---

## 📋 번역 구조 개요

### 번역 파일 위치
```
lang/
├── es-MX/
│   └── filament.php    # 스페인어 (멕시코) - 주 언어
├── en/
│   └── filament.php    # 영어 - 폴백 언어
└── ko/
    └── filament.php    # 한국어 - 추가 언어
```

⚠️ **중요**: Laravel 12에서는 `resources/lang/` 디렉토리를 사용하지 않습니다. 충돌 방지를 위해 반드시 `lang/` 디렉토리를 사용하세요.

### 번역 키 통계
- **총 번역 키**: 30개
- **지원 언어**: 3개 (es-MX, en, ko)
- **총 번역 수**: 90개
- **완성도**: 100%

---

## 🗂️ 번역 키 분류

### 1. Resource Metadata (리소스 메타데이터) - 3개

리소스 자체의 이름과 네비게이션 라벨.

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.resource.label` | Organización | Organization | 조직 |
| `organizations.resource.plural_label` | Organizaciones | Organizations | 조직 목록 |
| `organizations.resource.navigation_label` | Organizaciones | Organizations | 조직 관리 |

**사용 위치**:
- `OrganizationResource::getModelLabel()`
- `OrganizationResource::getPluralModelLabel()`
- `OrganizationResource::getNavigationLabel()`

---

### 2. Form Fields (폼 필드) - 5개

조직 생성/편집 폼의 입력 필드 라벨.

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.fields.name` | Nombre | Name | 이름 |
| `organizations.fields.description` | Descripción | Description | 설명 |
| `organizations.fields.contact_email` | Correo Electrónico de Contacto | Contact Email | 연락처 이메일 |
| `organizations.fields.contact_phone` | Teléfono de Contacto | Contact Phone | 연락처 전화번호 |
| `organizations.fields.is_active` | Estado Activo | Active Status | 활성 상태 |

**사용 위치**:
- `OrganizationForm::configure()`

**예시 코드**:
```php
TextInput::make('name')
    ->label(__('filament.organizations.fields.name'))
    ->required()
    ->maxLength(255),
```

---

### 3. Table Columns (테이블 컬럼) - 6개

조직 목록 테이블의 컬럼 헤더.

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.columns.name` | Nombre | Name | 이름 |
| `organizations.columns.contact_email` | Correo de Contacto | Contact Email | 연락처 이메일 |
| `organizations.columns.contact_phone` | Teléfono de Contacto | Contact Phone | 연락처 전화 |
| `organizations.columns.is_active` | Activo | Active | 활성 |
| `organizations.columns.created_at` | Fecha de Creación | Created At | 생성일 |
| `organizations.columns.updated_at` | Fecha de Actualización | Updated At | 수정일 |

**사용 위치**:
- `OrganizationsTable::configure()`

**예시 코드**:
```php
TextColumn::make('name')
    ->label(__('filament.organizations.columns.name'))
    ->searchable()
    ->sortable(),
```

---

### 4. Actions (액션 버튼) - 5개

테이블 및 폼에서 사용되는 액션 버튼 라벨.

#### 4.1 조직 전용 액션 (2개)

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.actions.activities` | Actividades | Activities | 활동 기록 |
| `organizations.actions.back` | Volver | Back | 돌아가기 |

**사용 위치**:
- `OrganizationsTable::configure()`
- `ListOrganizationActivities::getHeaderActions()`

#### 4.2 공통 액션 (3개)

모든 리소스에서 재사용 가능한 공통 액션.

| 키 | es-MX | en | ko |
|---|---|---|---|
| `common.actions.view` | Ver | View | 보기 |
| `common.actions.edit` | Editar | Edit | 편집 |
| `common.actions.delete` | Eliminar | Delete | 삭제 |

**사용 위치**:
- `OrganizationsTable::configure()`
- 향후 모든 Filament 리소스에서 재사용

**예시 코드**:
```php
ViewAction::make()
    ->label(__('filament.common.actions.view')),
```

---

### 5. Activities (활동 로그) - 14개

조직의 활동 기록 화면에서 사용되는 번역.

#### 5.1 화면 제목 및 필터

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.activities.title` | Registro de Actividades: :name | Activity Log: :name | 활동 기록: :name |
| `organizations.activities.filters.event_type` | Tipo de Evento | Event Type | 이벤트 유형 |

**사용 위치**:
- `ListOrganizationActivities::getTitle()`
- `ListOrganizationActivities::table()`

**예시 코드**:
```php
public function getTitle(): string | Htmlable
{
    $organization = $this->getRecord();
    return __('filament.organizations.activities.title', ['name' => $organization->name]);
}
```

#### 5.2 이벤트 유형 (3개)

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.activities.event_types.created` | Creado | Created | 생성됨 |
| `organizations.activities.event_types.updated` | Actualizado | Updated | 수정됨 |
| `organizations.activities.event_types.deleted` | Eliminado | Deleted | 삭제됨 |

#### 5.3 테이블 컬럼 (6개)

| 키 | es-MX | en | ko |
|---|---|---|---|
| `organizations.activities.columns.event` | Evento | Event | 이벤트 |
| `organizations.activities.columns.description` | Descripción | Description | 설명 |
| `organizations.activities.columns.causer` | Responsable | Causer | 담당자 |
| `organizations.activities.columns.properties` | Propiedades | Properties | 속성 |
| `organizations.activities.columns.created_at` | Fecha | Date | 날짜 |
| `organizations.activities.columns.subject_type` | Tipo de Sujeto | Subject Type | 대상 유형 |

---

## 🌐 언어별 특징

### es-MX (스페인어 - 멕시코)
- **역할**: 주 언어 (Primary locale)
- **특징**:
  - 멕시코 스페인어 표준 사용
  - 형식적인 어조 (Usted 형태)
  - 비즈니스 환경 적합한 용어 선택
- **예시**: "Correo Electrónico de Contacto" (Contact Email)

### en (영어)
- **역할**: 폴백 언어 (Fallback locale)
- **특징**:
  - 미국 영어 표준
  - Filament 기본 용어와 일치
  - 개발자 친화적 표현
- **예시**: "Contact Email"

### ko (한국어)
- **역할**: 추가 언어 (Additional locale)
- **특징**:
  - 존댓말 사용 안 함 (명사형)
  - 간결한 표현 선호
  - UI 공간 효율 고려
- **예시**: "연락처 이메일"

---

## 🔄 로케일 전환 동작

### 기본 동작
```php
// config/app.php
'locale' => 'es-MX',          // 기본 언어
'fallback_locale' => 'en',    // 폴백 언어
```

### 전환 시나리오
1. **사용자가 es-MX 선택** → es-MX 번역 사용
2. **사용자가 en 선택** → en 번역 사용
3. **사용자가 ko 선택** → ko 번역 사용
4. **번역 키 누락 시** → en 폴백 → es-MX 폴백

### 테스트 검증
```php
// OrganizationResourceI18nTest.php에서 검증됨
app()->setLocale('es-MX');
expect(__('filament.organizations.fields.name'))->toBe('Nombre');

app()->setLocale('en');
expect(__('filament.organizations.fields.name'))->toBe('Name');

app()->setLocale('ko');
expect(__('filament.organizations.fields.name'))->toBe('이름');
```

---

## 📐 번역 규칙

### 1. 일관성
- 동일한 개념은 동일한 단어로 번역
- 예: "Contact" → es-MX "Contacto", ko "연락처" (항상 동일)

### 2. 간결성
- UI 라벨은 2-4 단어로 제한
- 예외: 설명 필드 (description)

### 3. 명확성
- 기술 용어는 원어 병기 가능
- 예: "Correo Electrónico" (Email), "이메일" (Email)

### 4. 파라미터 규칙
동적 값은 `:variable` 형식 사용:
```php
'title' => 'Registro de Actividades: :name'
// 사용: __('...title', ['name' => $org->name])
```

---

## ✅ 번역 완성도 검증

### 자동 검증 (CI/CD)
`TranslationCompletenessTest.php`가 다음을 자동 검증:

1. **키 일치성**: 모든 언어가 동일한 키 보유
2. **파일 존재**: 3개 언어 파일 모두 존재
3. **구조 일치**: 중첩 배열 구조 동일

### 수동 검증 명령어
```bash
# 번역 키 개수 확인
php artisan lang:count filament

# 누락 번역 감지
php artisan lang:missing filament es-MX en ko

# 테스트 실행
php artisan test --filter=TranslationCompletenessTest
```

---

## 🚀 향후 확장 가이드

### 새 리소스 추가 시
1. `lang/{locale}/filament.php`에 새 섹션 추가:
   ```php
   'users' => [
       'resource' => [...],
       'fields' => [...],
       'columns' => [...],
       'actions' => [...],
   ],
   ```

2. `common.actions` 재사용:
   ```php
   EditAction::make()
       ->label(__('filament.common.actions.edit')),
   ```

3. 번역 완성도 테스트 자동 감지

### 새 언어 추가 시
1. `lang/pt-BR/filament.php` 생성 (예: 포르투갈어)
2. `config/app.php`에 로케일 추가
3. `TranslationCompletenessTest.php`에 언어 추가

---

## 🔗 관련 문서

- **SPEC 문서**: [spec.md](../../specs/SPEC-I18N-001/spec.md)
- **구현 보고서**: [I18N-001-implementation.md](./I18N-001-implementation.md)
- **Laravel 다국어 문서**: https://laravel.com/docs/12.x/localization
- **Filament 다국어 가이드**: https://filamentphp.com/docs/4.x/panels/translations

---

**작성일**: 2025-10-19
**작성자**: @user
**TAG**: @DOC:I18N-001
