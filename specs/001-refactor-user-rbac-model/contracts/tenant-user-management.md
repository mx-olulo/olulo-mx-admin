# API 계약: 테넌트 사용자 관리

**날짜**: 2025-10-20
**관련 SPEC**: [spec.md](../spec.md)

## 개요

Admin 사용자의 테넌트별 역할 관리를 위한 내부 API 계약입니다. 이 API는 Filament Admin 패널에서 사용되며, 외부 노출되지 않습니다.

---

## 1. 테넌트 역할 할당

### POST /api/internal/tenant-users

**설명**: Admin 사용자에게 특정 테넌트의 역할을 할당합니다.

**요청**:
```json
{
  "user_id": 123,
  "tenant_type": "ORG",
  "tenant_id": 5,
  "role": "owner"
}
```

**응답 (201 Created)**:
```json
{
  "success": true,
  "data": {
    "id": 789,
    "user_id": 123,
    "tenant_type": "ORG",
    "tenant_id": 5,
    "role": "owner",
    "created_at": "2025-10-20T10:00:00Z"
  }
}
```

**응답 (409 Conflict - 중복)**:
```json
{
  "success": false,
  "error": "User already has a role for this tenant"
}
```

**검증 규칙**:
- `user_id`: 필수, User 모델 존재 확인, user_type='admin'
- `tenant_type`: 필수, enum ('ORG', 'BRD', 'STR')
- `tenant_id`: 필수, 해당 tenant_type의 실제 레코드 존재 확인
- `role`: 필수, enum ('owner', 'manager', 'viewer')

---

## 2. 테넌트 역할 수정

### PATCH /api/internal/tenant-users/{id}

**설명**: 기존 테넌트 역할을 변경합니다.

**요청**:
```json
{
  "role": "manager"
}
```

**응답 (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 789,
    "user_id": 123,
    "tenant_type": "ORG",
    "tenant_id": 5,
    "role": "manager",
    "updated_at": "2025-10-20T11:00:00Z"
  }
}
```

**검증 규칙**:
- `role`: 필수, enum ('owner', 'manager', 'viewer')

---

## 3. 테넌트 역할 삭제

### DELETE /api/internal/tenant-users/{id}

**설명**: Admin 사용자의 특정 테넌트 접근 권한을 제거합니다.

**응답 (204 No Content)**: 본문 없음

**응답 (404 Not Found)**:
```json
{
  "success": false,
  "error": "Tenant user not found"
}
```

---

## 4. 사용자의 테넌트 목록 조회

### GET /api/internal/users/{userId}/tenants

**설명**: 특정 사용자가 접근 가능한 모든 테넌트 목록을 조회합니다.

**쿼리 파라미터**:
- `tenant_type` (optional): 'ORG', 'BRD', 'STR' 중 하나로 필터링

**응답 (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 789,
      "user_id": 123,
      "tenant_type": "ORG",
      "tenant_id": 5,
      "role": "owner",
      "tenant": {
        "id": 5,
        "name": "Organization A",
        "slug": "organization-a"
      }
    },
    {
      "id": 790,
      "user_id": 123,
      "tenant_type": "BRD",
      "tenant_id": 10,
      "role": "manager",
      "tenant": {
        "id": 10,
        "name": "Brand C",
        "slug": "brand-c"
      }
    }
  ]
}
```

---

## 5. 테넌트의 Admin 목록 조회

### GET /api/internal/tenants/{tenantType}/{tenantId}/admins

**설명**: 특정 테넌트에 접근 권한이 있는 모든 Admin 사용자 목록을 조회합니다.

**Path 파라미터**:
- `tenantType`: 'org', 'brand', 'store'
- `tenantId`: 테넌트 ID

**응답 (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 789,
      "user_id": 123,
      "role": "owner",
      "user": {
        "id": 123,
        "name": "John Admin",
        "email": "admin@example.com",
        "last_login_at": "2025-10-20T09:00:00Z"
      }
    },
    {
      "id": 791,
      "user_id": 125,
      "role": "viewer",
      "user": {
        "id": 125,
        "name": "Jane Viewer",
        "email": "viewer@example.com",
        "last_login_at": "2025-10-19T15:00:00Z"
      }
    }
  ]
}
```

---

## 인증 및 권한

### 인증 방식
- Laravel Sanctum 세션 기반 인증 (`auth:web`)
- Filament Admin 패널에서만 접근 가능

### 권한 검증
- `POST /api/internal/tenant-users`: owner 또는 manager 역할 필요
- `PATCH /api/internal/tenant-users/{id}`: owner 역할 필요
- `DELETE /api/internal/tenant-users/{id}`: owner 역할 필요
- `GET` 엔드포인트: 해당 테넌트의 viewer 이상 역할 필요

---

## 오류 응답 형식

**표준 오류 응답**:
```json
{
  "success": false,
  "error": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**HTTP 상태 코드**:
- `200 OK`: 성공적인 조회
- `201 Created`: 성공적인 생성
- `204 No Content`: 성공적인 삭제
- `400 Bad Request`: 잘못된 요청
- `401 Unauthorized`: 인증 실패
- `403 Forbidden`: 권한 부족
- `404 Not Found`: 리소스 없음
- `409 Conflict`: 중복 또는 충돌
- `422 Unprocessable Entity`: 검증 실패
- `500 Internal Server Error`: 서버 오류

---

## 감사 로깅

모든 테넌트 역할 변경 작업은 Spatie Activity Log에 기록됩니다:

```php
activity('tenant_user')
    ->performedOn($tenantUser)
    ->causedBy(auth()->user())
    ->log('assigned role ' . $tenantUser->role);
```

**로그 포함 정보**:
- 작업자 (causer_id, causer_type)
- 대상 (subject_id, subject_type)
- 변경 내용 (properties: old/new values)
- 타임스탬프 (created_at)

---

**작성자**: Claude Code
**리뷰 필요**: 구현 전 API 검토
