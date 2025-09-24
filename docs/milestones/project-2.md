# 프로젝트 2 — 메뉴/테이블/QR/초기 주문 접수

## 목적
- 메뉴/테이블 기본 등록과 테이블별 QR 생성
- QR 기반으로 고객 페이지 진입(토큰 검증) 및 메뉴 표시(목록/검색 기초)
- 주문 생성(초기: 카트→주문 생성까지, 결제 제외)

## 범위(MVF)
- Admin(Filament): 메뉴/카테고리/테이블 CRUD(간단 필드), QR 토큰 생성/재생성
- QR: `/c/{store}/{table}?seat&token` → 토큰 검증 → `/app?store&table&seat` 리다이렉트
- 고객 React: 메뉴 목록/상세(옵션 제외), 카트 담기, 주문 생성(API)까지
- 백엔드: `orders`/`order_sessions` 기본 생성 로직, 상태 `pending` 등록

## 화면/기능(Step-by-step)
1) Admin
- 메뉴: 이름/가격(기본 통화)/이미지 URL(임시) 등록
- 카테고리: 단일 계층, 메뉴 연결
- 테이블: 코드/이름/좌석 수 등록
- QR: 테이블별 토큰 생성/만료/재생성 버튼

2) 고객 React
- QR 진입 → 세션 부착 → 메뉴 목록 표시(검색 텍스트 박스)
- 메뉴 상세: 수량만 반영, 옵션/세트는 추후
- 카트 → 주문 생성 → 성공 화면(주문 번호)

## 의존 라이브러리(추천)
- QR 생성
  - simplesoftwareio/simple-qrcode 또는 endroid/qr-code
- 이미지
  - spatie/image (추후 썸네일/검증에 유용)
- 권한 관리(기초)
  - spatie/laravel-permission (관리/마스터 분리 대비)
- 파일 업로드(추후 확장)
  - spatie/laravel-medialibrary (이미지 관리 고도화 대비)

## 데이터/API
- 스키마: `stores`, `store_tables`, `qr_tokens`, `menus`, `menu_categories`, `orders`, `order_sessions`
- API 요약
  - GET `/api/v1/customer/menus`
  - POST `/api/v1/customer/session`
  - POST `/api/v1/customer/orders`
  - GET `/api/v1/customer/orders/{id}`
  - Admin CRUD 엔드포인트(Filament 리소스)

## 산출물/검증 포인트
- 테이블별 QR 이미지 파일/URL, 재생성 확인
- 고객 앱에서 QR 진입 → 메뉴 → 주문 생성까지 시연
- Admin에서 등록한 메뉴가 고객 앱에 표시됨을 확인

## TODO(보강 문서)
- TODO: `docs/qr/format.md` — QR 포맷과 보안 토큰 설계 상세
- TODO: `docs/menus/modeling.md` — 메뉴/카테고리 데이터 모델 가이드
- TODO: `docs/orders/flow-basic.md` — 기본 주문 생성/세션 플로우 상세
