# Organizations 설계 (최소 단위)

Organizations는 회사(법인) 단위의 상위 엔터티입니다. Stores는 조직에 속할 수도 있고, 독립적으로 운영될 수도 있습니다.

## 스키마 (초안)
- id (PK)
- name: string(255)
- slug: string(255, unique)
- is_active: boolean (default: true)
- contact_email: string(255, nullable)
- contact_phone: string(50, nullable)
- settings: json (nullable)
- timestamps

## 모델
- 파일: `app/Models/Organization.php`
- fillable: name, slug, is_active, contact_email, contact_phone, settings
- casts: is_active(bool), settings(array)

## 관리(Management)
- 우선 Filament로 CRUD 제공 (Public API는 보류)
- 접근 제어는 추후 `OrganizationPolicy` 및 Role 기반으로 강화

## 확장 포인트
- Stores와의 관계: `stores.organization_id` (nullable)
- 조직 관리자(organization_managers) 피벗 (차기 단계)
- 회계/세금/영업시간 등 설정의 JSON 스키마 구체화

## 우선순위 작업
1) 마이그레이션/모델 작성
2) Filament Resource 생성 (List/Create/Edit)
3) 기본 검증 및 unique(slug) 보장
