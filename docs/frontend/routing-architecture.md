# 고객앱 라우팅 아키텍처

## 설계 원칙
- **도메인**: `menu.olulo.com.mx` (고객 전용)
- **관리자 분리**: `/customer/auth/*` (관리자 `/auth/login`과 충돌 방지)
- **예약어**: `customer`, `my`, `auth`, `admin`, `nova`, `api`

## 라우트 구조 (Phase 1)

### 고객 라우트
```
/                           # QR 진입 (파라미터 처리)
/customer/auth/login        # 고객 로그인
/my/orders                  # 마이페이지
```

### 관리자 라우트 (기존 유지)
```
/auth/login                 # 관리자 로그인
/admin                      # Filament
/nova                       # Nova
```

## URL 예시
```
# QR 스캔
/?store=tacos-maya&table=5&seat=2

# 로그인
/customer/auth/login

# 마이페이지
/my/orders
```

## 향후 확장 (문서로만)
```
/menu                       # 메뉴 조회
/cart                       # 장바구니
/pickup                     # 픽업 주문
/{store}/menu               # 매장별 메뉴 (경로 기반)
```

## 충돌 방지
- 매장 slug는 예약어 사용 불가
- 예약어 라우트를 동적 라우트보다 먼저 정의
- 서브도메인 기반 테넌시는 후속 작업

## 관련 문서
- 이슈 #4 범위: [issue-4-scope.md](issue-4-scope.md)
- 인증 설계: [../auth.md](../auth.md)
- 테넌시: [../tenancy/host-middleware.md](../tenancy/host-middleware.md)
