# RBAC (roles, permissions, role_user, permission_role)

- 목적: 표준 역할/권한 기반 접근 제어

## 테이블 개요
- `roles(id, name, slug, ... )`
- `permissions(id, name, slug, ... )`
- `role_user(id, role_id FK, user_id FK)` 또는 `model_has_roles` 패턴
- `permission_role(id, permission_id FK, role_id FK)`

## 인덱스/제약
- roles.slug unique, permissions.slug unique
- role_user: (role_id, user_id) composite unique
- permission_role: (permission_id, role_id) composite unique

## 관계
- N:N 사용자-역할, 역할-권한

## 마이그레이션 가이드
- Laravel Permission 패키지 호환 스키마 고려 가능
