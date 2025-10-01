# 고객앱 언어 처리 전략

## 문서 목적
고객앱의 다국어 지원 전략을 정의하고, 브라우저 언어 자동 감지부터 수동 전환까지의 전체 플로우를 설명합니다.

## 관련 문서
- 이슈 #4 범위 명세: [docs/frontend/issue-4-scope.md](issue-4-scope.md)
- 라우팅 아키텍처: [docs/frontend/routing-architecture.md](routing-architecture.md)
- 화이트페이퍼: [docs/whitepaper.md](../whitepaper.md)
- 프로젝트 1 계획: [docs/milestones/project-1.md](../milestones/project-1.md)

## 지원 언어

### 기본 언어 목록
1. **한국어 (ko)** — 한국 사용자 대상
2. **스페인어 (es-MX)** — 멕시코 주요 언어 (기본값)
3. **영어 (en)** — 국제 사용자 대상

### 우선순위
- **멕시코 시장**: `es-MX` > `en` > `ko`
- **개발/테스트**: `ko` (한국 팀)

## 언어 감지 전략

### 1. 자동 감지 (초기 진입)
**우선순위**:
1. **URL 쿼리 파라미터**: `?lang=ko`
2. **로컬 스토리지**: `localStorage.getItem('preferred_locale')`
3. **브라우저 언어**: `navigator.language` 또는 `Accept-Language` 헤더
4. **기본값**: `es-MX` (멕시코 시장 기준)

### 2. 브라우저 언어 감지 로직
**프론트엔드** (JavaScript):
```typescript
// resources/js/lib/locale.ts (향후 구현)

const SUPPORTED_LOCALES = ['ko', 'es-MX', 'en'];
const DEFAULT_LOCALE = 'es-MX';

function detectBrowserLocale(): string {
    // 1. URL 쿼리 파라미터
    const params = new URLSearchParams(window.location.search);
    const queryLang = params.get('lang');
    if (queryLang && SUPPORTED_LOCALES.includes(queryLang)) {
        return queryLang;
    }

    // 2. 로컬 스토리지
    const storedLocale = localStorage.getItem('preferred_locale');
    if (storedLocale && SUPPORTED_LOCALES.includes(storedLocale)) {
        return storedLocale;
    }

    // 3. 브라우저 언어
    const browserLang = navigator.language || navigator.userLanguage;

    // 정확한 매칭: es-MX
    if (SUPPORTED_LOCALES.includes(browserLang)) {
        return browserLang;
    }

    // 부분 매칭: es-MX → es, ko-KR → ko
    const langPrefix = browserLang.split('-')[0];
    const matched = SUPPORTED_LOCALES.find(locale => locale.startsWith(langPrefix));
    if (matched) {
        return matched;
    }

    // 4. 기본값
    return DEFAULT_LOCALE;
}

export { detectBrowserLocale };
```

**백엔드** (Laravel Middleware):
```php
// app/Http/Middleware/LocaleMiddleware.php (이슈 #4에서 구현)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocaleMiddleware
{
    private const SUPPORTED_LOCALES = ['ko', 'es-MX', 'en'];
    private const DEFAULT_LOCALE = 'es-MX';

    public function handle(Request $request, Closure $next)
    {
        $locale = $this->detectLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    private function detectLocale(Request $request): string
    {
        // 1. URL 쿼리 파라미터
        if ($request->has('lang') && in_array($request->get('lang'), self::SUPPORTED_LOCALES)) {
            return $request->get('lang');
        }

        // 2. Accept-Language 헤더
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale) {
                return $locale;
            }
        }

        // 3. 기본값
        return self::DEFAULT_LOCALE;
    }

    private function parseAcceptLanguage(string $header): ?string
    {
        // Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7
        preg_match_all('/([a-z]{2}(?:-[A-Z]{2})?)/i', $header, $matches);

        foreach ($matches[1] as $lang) {
            if (in_array($lang, self::SUPPORTED_LOCALES)) {
                return $lang;
            }

            // 부분 매칭: ko-KR → ko
            $prefix = explode('-', $lang)[0];
            $matched = collect(self::SUPPORTED_LOCALES)->first(fn($l) => str_starts_with($l, $prefix));
            if ($matched) {
                return $matched;
            }
        }

        return null;
    }
}
```

## 수동 언어 전환

### 1. UI 컴포넌트 (향후 구현)
**위치**: 헤더, 설정 페이지

**컴포넌트 예시**:
```tsx
// resources/js/Components/LanguageSwitcher.tsx (향후)

import { useState } from 'react';

const LANGUAGES = [
    { code: 'ko', label: '한국어', flag: '🇰🇷' },
    { code: 'es-MX', label: 'Español', flag: '🇲🇽' },
    { code: 'en', label: 'English', flag: '🇺🇸' },
];

export default function LanguageSwitcher() {
    const [currentLocale, setCurrentLocale] = useState(
        localStorage.getItem('preferred_locale') || 'es-MX'
    );

    const handleChange = (locale: string) => {
        // 1. 로컬 스토리지 저장
        localStorage.setItem('preferred_locale', locale);

        // 2. 백엔드에 선호 언어 전송 (API, 선택적)
        axios.post('/api/user/locale', { locale });

        // 3. 페이지 새로고침 (i18n 적용)
        window.location.reload();
    };

    return (
        <select value={currentLocale} onChange={(e) => handleChange(e.target.value)}>
            {LANGUAGES.map((lang) => (
                <option key={lang.code} value={lang.code}>
                    {lang.flag} {lang.label}
                </option>
            ))}
        </select>
    );
}
```

### 2. 사용자 선호 언어 저장 (향후)
**백엔드 API**:
```php
// routes/api.php (향후)

Route::post('/user/locale', function (Request $request) {
    $request->validate([
        'locale' => ['required', Rule::in(['ko', 'es-MX', 'en'])],
    ]);

    // 로그인 사용자의 경우 DB에 저장
    if ($user = $request->user()) {
        $user->update(['preferred_locale' => $request->locale]);
    }

    return response()->noContent();
});
```

**DB 스키마 (향후)**:
```php
// database/migrations/xxxx_add_locale_to_users_table.php

Schema::table('users', function (Blueprint $table) {
    $table->string('preferred_locale', 10)->default('es-MX')->after('email');
});
```

## i18n 라이브러리 통합 (향후)

### 1. react-i18next 설정
**설치**:
```bash
npm install react-i18next i18next i18next-browser-languagedetector i18next-http-backend
```

**설정 파일**:
```typescript
// resources/js/i18n.ts (향후)

import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import HttpBackend from 'i18next-http-backend';

i18n
    .use(HttpBackend) // 백엔드에서 번역 파일 로드
    .use(LanguageDetector) // 브라우저 언어 자동 감지
    .use(initReactI18next) // React 통합
    .init({
        fallbackLng: 'es-MX',
        supportedLngs: ['ko', 'es-MX', 'en'],
        detection: {
            order: ['querystring', 'localStorage', 'navigator'],
            caches: ['localStorage'],
            lookupQuerystring: 'lang',
            lookupLocalStorage: 'preferred_locale',
        },
        backend: {
            loadPath: '/lang/{{lng}}/messages.json', // Laravel 번역 파일 경로
        },
        interpolation: {
            escapeValue: false, // React가 이미 XSS 보호
        },
    });

export default i18n;
```

### 2. 번역 파일 구조
**Laravel 번역 파일**:
```
lang/
├── ko/
│   └── messages.json
├── es-MX/
│   └── messages.json
└── en/
    └── messages.json
```

**messages.json 예시** (ko):
```json
{
    "app": {
        "title": "Olulo MX - 고객앱",
        "welcome": "환영합니다",
        "login": "로그인하기",
        "logout": "로그아웃",
        "continue_as_guest": "계속하기 (비회원)"
    },
    "menu": {
        "title": "메뉴",
        "category": "카테고리",
        "search": "검색",
        "add_to_cart": "장바구니에 추가"
    },
    "auth": {
        "login_title": "로그인",
        "email": "이메일",
        "password": "비밀번호",
        "forgot_password": "비밀번호를 잊으셨나요?",
        "sign_up": "회원가입"
    }
}
```

### 3. React 컴포넌트에서 사용
```tsx
// resources/js/Pages/App.tsx (향후)

import { useTranslation } from 'react-i18next';

export default function App() {
    const { t } = useTranslation();

    return (
        <div>
            <h1>{t('app.title')}</h1>
            <button>{t('app.login')}</button>
            <button>{t('app.continue_as_guest')}</button>
        </div>
    );
}
```

## 번역 관리 전략

### 1. 초기 번역 (수동)
- 한국어 → 스페인어/영어: 팀 내 번역 또는 전문 번역가
- 핵심 UI 문구 우선 (버튼, 에러 메시지, 안내문)

### 2. 동적 번역 (AI, 향후)
**메뉴 이름/설명 자동 번역**:
- AI 번역 API (예: Google Translate API, DeepL)
- 초안 생성 → 관리자 승인/수정 → DB 저장
- `menu_translations` 테이블 활용 (화이트페이퍼 참조)

**플로우**:
1. 매장 관리자가 메뉴 입력 (한국어)
2. 백엔드에서 AI 번역 API 호출 (스페인어, 영어)
3. 초안을 `menu_translations`에 저장 (승인 대기)
4. 관리자 검토/수정 → 승인
5. 고객앱에서 승인된 번역 표시

### 3. 크라우드소싱 (장기 계획)
- 사용자 제안 번역 기능
- 관리자 승인 후 반영

## 언어별 포맷 처리

### 1. 날짜/시간
**라이브러리**: `date-fns`, `dayjs` (i18n 지원)

**예시**:
```typescript
import { format } from 'date-fns';
import { ko, es, enUS } from 'date-fns/locale';

const locales = { ko, 'es-MX': es, en: enUS };

const formattedDate = format(new Date(), 'PPP', {
    locale: locales[currentLocale],
});
// ko: 2025년 10월 2일
// es-MX: 2 de octubre de 2025
// en: October 2, 2025
```

### 2. 숫자/통화
**라이브러리**: `Intl.NumberFormat` (브라우저 내장)

**예시**:
```typescript
const price = 12000;

// 한국어 (KRW)
new Intl.NumberFormat('ko', { style: 'currency', currency: 'KRW' }).format(price);
// ₩12,000

// 스페인어 (MXN)
new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(price);
// $12,000.00

// 영어 (USD)
new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price);
// $12,000.00
```

**참고**: 통화는 별도 선택 가능 (언어와 독립, 화이트페이퍼 참조)

### 3. 복수형 처리
**react-i18next 지원**:
```json
{
    "items": "{{count}}개 아이템",
    "items_plural": "{{count}}개 아이템"
}
```

```tsx
t('items', { count: 1 }); // "1개 아이템"
t('items', { count: 5 }); // "5개 아이템"
```

## RTL(오른쪽에서 왼쪽) 지원 (향후)
- 아랍어 등 RTL 언어 추가 시 고려
- TailwindCSS RTL 플러그인 활용
- `dir="rtl"` 속성 자동 설정

## SEO 다국어 지원 (향후)

### 1. hreflang 태그
```html
<link rel="alternate" hreflang="ko" href="https://store1.olulo.com.mx/app?lang=ko" />
<link rel="alternate" hreflang="es-MX" href="https://store1.olulo.com.mx/app?lang=es-MX" />
<link rel="alternate" hreflang="en" href="https://store1.olulo.com.mx/app?lang=en" />
<link rel="alternate" hreflang="x-default" href="https://store1.olulo.com.mx/app" />
```

### 2. 언어별 메타데이터
```html
<html lang="es-MX">
<head>
    <title>Olulo MX - Pedido en línea</title>
    <meta name="description" content="Ordena tu comida favorita en línea" />
</head>
```

## 테스트 시나리오

### 1. 브라우저 언어 감지
- [ ] 브라우저 언어 `ko` → 한국어로 표시
- [ ] 브라우저 언어 `es-MX` → 스페인어로 표시
- [ ] 브라우저 언어 `en` → 영어로 표시
- [ ] 지원하지 않는 언어 (`fr`) → 기본값(`es-MX`)으로 표시

### 2. URL 쿼리 파라미터
- [ ] `/app?lang=ko` → 한국어로 표시 (브라우저 언어 무시)
- [ ] `/app?lang=en` → 영어로 표시
- [ ] `/app?lang=invalid` → 기본값(`es-MX`)으로 표시

### 3. 로컬 스토리지
- [ ] 로컬 스토리지 `preferred_locale=ko` → 한국어로 표시
- [ ] 언어 전환 → 로컬 스토리지 업데이트 → 새로고침 시 유지

### 4. 수동 전환
- [ ] 언어 전환 UI → 즉시 적용 (새로고침 또는 동적 변경)
- [ ] 전환 후 모든 문구 변경 확인 (버튼, 에러 메시지 등)

### 5. 포맷 처리
- [ ] 날짜/시간이 언어별 형식으로 표시
- [ ] 통화 기호가 선택한 통화에 맞게 표시
- [ ] 복수형 처리 정확성 확인

## 구현 우선순위

### 프로젝트 1 (이슈 #4)
- [x] LocaleMiddleware 구현 (Accept-Language 감지)
- [x] 지원 언어 정의 (`ko`, `es-MX`, `en`)
- [ ] 브라우저 언어 감지 로직 (프론트엔드, placeholder)
- [ ] 언어 처리 전략 문서 (본 문서)

### 프로젝트 2 (후속 이슈)
- [ ] react-i18next 설치 및 설정
- [ ] 번역 파일 구조 생성 (`lang/{locale}/messages.json`)
- [ ] 핵심 UI 문구 번역 (한국어, 스페인어, 영어)
- [ ] LanguageSwitcher 컴포넌트 구현

### 프로젝트 3 (후속 이슈)
- [ ] AI 번역 API 통합 (메뉴 자동 번역)
- [ ] 사용자 선호 언어 DB 저장
- [ ] 날짜/시간/통화 포맷 처리
- [ ] SEO hreflang 태그 자동 생성

## 관련 기술 스택
- **백엔드**: Laravel 12 (LocaleMiddleware, 번역 파일 라우팅)
- **프론트엔드**: React + react-i18next
- **번역 관리**: Laravel 번역 파일 (`lang/` 디렉터리)
- **포맷 처리**: `date-fns`, `Intl` API

## 참고 문서
- react-i18next 문서: https://react.i18next.com/
- Laravel 다국어: https://laravel.com/docs/12.x/localization
- Intl API: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl
- 화이트페이퍼 (i18n 전략): [docs/whitepaper.md](../whitepaper.md)
