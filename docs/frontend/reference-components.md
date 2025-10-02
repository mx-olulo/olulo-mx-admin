# 레퍼런스에서 가져온 컴포넌트 목록

본 문서는 `/ref` 디렉토리의 레퍼런스 UI에서 실제 프로젝트로 가져온 컴포넌트들을 정리합니다.

## 개요

- **소스**: `/ref/src/components/`
- **대상**: `/resources/js/components/`
- **목적**: 레퍼런스 UI 디자인을 실제 프로젝트에 적용
- **작업일**: 2025-10-02

---

## 가져온 컴포넌트

### 1. BottomNavigation

**파일 경로**
- 소스: `ref/src/components/BottomNavigation.tsx`
- 대상: `resources/js/components/BottomNavigation.tsx`

**설명**
- 하단 고정 네비게이션 바
- 5개 탭: HOME, ORDERS, QR CODE, POINTS, ADMIN
- SVG 아이콘 포함 (인라인 path 데이터)

**주요 기능**
- Active/Inactive 상태별 색상 변경 (#00B96F / #878787)
- 픽업 모드 / 테이블 주문 모드 지원
- 장바구니 카운터 표시
- 다국어 지원 (ko, es, en)
- 다크모드 지원

**의존성**
- SVG paths: 파일 상단에 `svgPaths` 객체로 인라인 포함
- React 19.1

**사용 예시**
```tsx
<BottomNavigation
  activeTab="home"
  onTabChange={(tabId) => console.log(tabId)}
  language="ko"
/>
```

---

### 2. Header

**파일 경로**
- 소스: `ref/src/components/Header.tsx`
- 대상: `resources/js/components/Header.tsx`

**설명**
- 기본 헤더 컴포넌트
- Primary 색상 배경 (#03D67B)
- 뒤로가기 버튼, 타이틀, 위치 버튼

**주요 기능**
- 뒤로가기 버튼 (ArrowLeft 아이콘)
- 위치 버튼 (MapPin 아이콘)
- Sticky 헤더 (상단 고정)
- 다크모드 지원

**의존성**
- lucide-react (ArrowLeft, MapPin 아이콘)

**사용 예시**
```tsx
<Header
  title="내 주문"
  showBack={true}
  showLocation={false}
  onBack={() => window.history.back()}
/>
```

---

### 3. LoginHeader

**파일 경로**
- 소스: `ref/src/components/LoginHeader.tsx`
- 대상: `resources/js/components/LoginHeader.tsx`

**설명**
- 비로그인 사용자용 헤더
- 로그인 유도 프로모션 텍스트
- 로그인 버튼

**주요 기능**
- 프로모션 텍스트: "🎁 혜택 폭발! 로그인 필수"
- 로그인 버튼 (흰색 배경)
- 다국어 지원 (ko, es, en)
- 다크모드 지원

**의존성**
- 없음 (BenefitsModal은 제거하고 TODO로 표시)

**사용 예시**
```tsx
<LoginHeader
  onLoginClick={() => router.visit('/customer/auth/login')}
  language="ko"
/>
```

**주의사항**
- 원본의 `BenefitsModal` 기능은 구현되지 않음 (TODO)
- 혜택 텍스트 클릭 시 콘솔 로그만 출력

---

## CustomerLayout 적용

`resources/js/Layouts/CustomerLayout.tsx`에서 세 컴포넌트 모두 사용 가능하도록 통합되었습니다.

**Props 변경사항**

기존:
```typescript
interface Props {
  showHeader?: boolean;
}
```

변경 후:
```typescript
interface Props {
  headerType?: 'default' | 'login' | 'none';
  onLoginClick?: () => void;
  language?: Language;
}
```

**사용 예시**

```tsx
// 기본 헤더
<CustomerLayout
  headerType="default"
  title="내 주문"
  showBack={true}
>
  {children}
</CustomerLayout>

// 로그인 헤더
<CustomerLayout
  headerType="login"
  language="ko"
  onLoginClick={() => router.visit('/customer/auth/login')}
>
  {children}
</CustomerLayout>

// 헤더 없음
<CustomerLayout headerType="none">
  {children}
</CustomerLayout>
```

---

## 에셋 파일

**이미지 파일 복사**
- 소스: `ref/src/assets/*.png` (12개 파일, 총 2.8MB)
- 대상: `resources/images/*.png`

**파일 목록**
1. `218ebb12add14565ca6a2ecc5e4fc0ad59cc1475.png` (70K)
2. `3d942e2d23030cb3b6df3d976297b7e976331839.png` (98K)
3. `6d63da8f36fce4149e9ec708e559f42fdc7900c2.png` (111K)
4. `744d664795beed4ed7cf02e1707bd3bc25ff6a4a.png` (130K)
5. `8340718cd1c7092f8b5bbdd0d6c4d54b807fb101.png` (457K)
6. `8930d82326c4713b3aca79806b2d7b6372a56841.png` (302K)
7. `8d1fabb50638fc86bc48442062a8a03e4efef188.png` (133K)
8. `9204cd8d0ed7f28ee6b064501aa6fd3daa18404c.png` (442K)
9. `95ede5fb3682aa138f7650cdab4575a20eed1d05.png` (472K)
10. `a4dda9978d6fadeb765d9dbc0a1170ba785f0c0b.png` (248K)
11. `c2c3a5209a7478d42ff828bcf01c35ff095ec5fa.png` (250K)
12. `df944f028f329b56dc5441c3cd76acb0f2d4f6df.png` (139K)

**사용 예시**
```tsx
import heroImage from '@/images/8340718cd1c7092f8b5bbdd0d6c4d54b807fb101.png';

<img src={heroImage} alt="Hero" />
```

---

## 향후 추가 가능한 컴포넌트

레퍼런스에 존재하나 아직 복사하지 않은 컴포넌트:

### PickupHeader
- 픽업/테이블 주문용 헤더
- Secondary 색상 배경 (#522cc6)
- 알림 아이콘 + 카운터
- 장바구니 아이콘 + 카운터
- 언어 설정 버튼

**필요 시 복사 방법**
```bash
# SVG paths도 함께 복사 필요
cp ref/src/imports/svg-mqj91w00zx.ts resources/js/imports/
cp ref/src/components/PickupHeader.tsx resources/js/components/
```

---

## 참고 문서

- [React Bootstrap 가이드](./react-bootstrap.md)
- [디자인 가이드라인](../../CLAUDE.md#design-guidelines)
- [Tailwind 설정](../../tailwind.config.js)

---

**마지막 업데이트**: 2025-10-02
**작성자**: Claude Code
