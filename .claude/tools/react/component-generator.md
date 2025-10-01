# React 19.1 컴포넌트 생성기

React 19.1 기반 TypeScript 컴포넌트를 프로젝트 규칙에 맞게 자동 생성하는 도구입니다.

## 사용법
```
/tools/react:component-generator [컴포넌트명] [타입] [옵션]
```

## 기본 동작

당신은 React 19.1 + TypeScript 전문가로서 현대적이고 타입 안전한 컴포넌트를 생성합니다. PWA 고객 앱에 최적화된 컴포넌트를 만들어야 합니다.

### 지원하는 컴포넌트 타입

1. **페이지 컴포넌트** - 라우트 레벨 페이지
2. **레이아웃 컴포넌트** - 공통 레이아웃
3. **폼 컴포넌트** - 입력 폼 및 검증
4. **UI 컴포넌트** - 재사용 가능한 UI 요소
5. **비즈니스 컴포넌트** - 도메인 특화 로직
6. **훅 컴포넌트** - 커스텀 훅

### 프로젝트 특화 설정

#### 기술 스택 통합
- **React 19.1** + TypeScript 5.0+
- **TailwindCSS** + daisyUI 스타일링
- **React Hook Form** + Zod 검증
- **React Query** 상태 관리
- **React Router v6** 라우팅
- **React-i18next** 다국어

#### PWA 최적화
- 오프라인 대응 컴포넌트
- 서비스 워커 통합
- 푸시 알림 UI
- 앱 설치 프롬프트

#### 멕시코 전자상거래 특화
- 멕시코 페소(MXN) 표시
- CURP/RFC 입력 컴포넌트
- WhatsApp 링크 생성
- QR 코드 스캔 지원

### 실행 프로세스

사용자 요청 "$ARGUMENTS"을 분석하여:

1. **컴포넌트 타입 결정**
   - 기능적 요구사항 분석
   - 적절한 패턴 선택
   - 의존성 파악

2. **파일 구조 생성**
   ```
   src/components/[ComponentName]/
   ├── index.ts
   ├── [ComponentName].tsx
   ├── [ComponentName].types.ts
   ├── [ComponentName].styles.ts (필요시)
   └── [ComponentName].test.tsx
   ```

3. **컴포넌트 코드 생성**
   - TypeScript 인터페이스 정의
   - React 함수형 컴포넌트 구현
   - Props 검증 및 기본값
   - 접근성(a11y) 고려

4. **테스트 코드 생성**
   - Jest + React Testing Library
   - 단위 테스트 케이스
   - 사용자 상호작용 테스트

### 컴포넌트 생성 패턴

#### 기본 컴포넌트 템플릿
```typescript
// [ComponentName].types.ts
export interface [ComponentName]Props {
  children?: React.ReactNode;
  className?: string;
  // 추가 props...
}

// [ComponentName].tsx
import React from 'react';
import { cn } from '@/lib/utils';
import type { [ComponentName]Props } from './[ComponentName].types';

export const [ComponentName]: React.FC<[ComponentName]Props> = ({
  children,
  className,
  ...props
}) => {
  return (
    <div
      className={cn(
        // 기본 스타일
        'component-base-styles',
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
};

[ComponentName].displayName = '[ComponentName]';

// index.ts
export { [ComponentName] } from './[ComponentName]';
export type { [ComponentName]Props } from './[ComponentName].types';
```

#### 폼 컴포넌트 패턴
```typescript
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslation } from 'react-i18next';

const schema = z.object({
  name: z.string().min(1, 'validation.required'),
  email: z.string().email('validation.email'),
  rfc: z.string().regex(/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/, 'validation.rfc'),
});

type FormData = z.infer<typeof schema>;

export const ContactForm: React.FC = () => {
  const { t } = useTranslation();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  const onSubmit = async (data: FormData) => {
    // 폼 제출 로직
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="form-control">
        <label className="label">
          <span className="label-text">{t('form.name')}</span>
        </label>
        <input
          {...register('name')}
          className={`input input-bordered ${errors.name ? 'input-error' : ''}`}
          placeholder={t('form.name_placeholder')}
        />
        {errors.name && (
          <span className="label-text-alt text-error">
            {t(errors.name.message)}
          </span>
        )}
      </div>

      <button
        type="submit"
        disabled={isSubmitting}
        className="btn btn-primary w-full"
      >
        {isSubmitting ? (
          <span className="loading loading-spinner loading-sm" />
        ) : (
          t('form.submit')
        )}
      </button>
    </form>
  );
};
```

#### 비즈니스 로직 컴포넌트
```typescript
import { useQuery } from '@tanstack/react-query';
import { useAuth } from '@/hooks/useAuth';
import { menuApi } from '@/services/api';

export const MenuList: React.FC = () => {
  const { tenant } = useAuth();

  const { data: menu, isLoading, error } = useQuery({
    queryKey: ['menu', tenant.id],
    queryFn: () => menuApi.getMenu(tenant.id),
    staleTime: 5 * 60 * 1000, // 5분
  });

  if (isLoading) {
    return <MenuSkeleton />;
  }

  if (error) {
    return <ErrorBoundary error={error} />;
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {menu?.items.map((item) => (
        <MenuCard
          key={item.id}
          item={item}
          onAddToCart={(item) => handleAddToCart(item)}
        />
      ))}
    </div>
  );
};
```

#### 커스텀 훅 패턴
```typescript
// hooks/useLocalStorage.ts
import { useState, useEffect } from 'react';

export function useLocalStorage<T>(
  key: string,
  initialValue: T
): [T, (value: T | ((val: T) => T)) => void] {
  const [storedValue, setStoredValue] = useState<T>(() => {
    try {
      const item = window.localStorage.getItem(key);
      return item ? JSON.parse(item) : initialValue;
    } catch (error) {
      console.error(`Error reading localStorage key "${key}":`, error);
      return initialValue;
    }
  });

  const setValue = (value: T | ((val: T) => T)) => {
    try {
      const valueToStore = value instanceof Function ? value(storedValue) : value;
      setStoredValue(valueToStore);
      window.localStorage.setItem(key, JSON.stringify(valueToStore));
    } catch (error) {
      console.error(`Error setting localStorage key "${key}":`, error);
    }
  };

  return [storedValue, setValue];
}
```

### 특별 처리 패턴

#### 멕시코 특화 컴포넌트
- **RFCInput**: RFC 검증 입력 컴포넌트
- **CURPInput**: CURP 검증 입력 컴포넌트
- **CurrencyDisplay**: 멕시코 페소 표시
- **WhatsAppButton**: WhatsApp 링크 버튼

#### PWA 컴포넌트
- **InstallPrompt**: 앱 설치 안내
- **OfflineBanner**: 오프라인 상태 표시
- **PushNotification**: 푸시 알림 설정

#### 접근성 최적화
- ARIA 속성 자동 추가
- 키보드 내비게이션 지원
- 스크린 리더 최적화
- 색상 대비 검증

### 테스트 코드 생성

```typescript
// [ComponentName].test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { vi } from 'vitest';
import { [ComponentName] } from './[ComponentName]';

const mockProps = {
  // 테스트용 props
};

describe('[ComponentName]', () => {
  it('renders correctly', () => {
    render(<[ComponentName] {...mockProps} />);
    expect(screen.getByRole('button')).toBeInTheDocument();
  });

  it('handles user interaction', async () => {
    const onClickMock = vi.fn();
    render(<[ComponentName] {...mockProps} onClick={onClickMock} />);

    fireEvent.click(screen.getByRole('button'));
    expect(onClickMock).toHaveBeenCalledTimes(1);
  });

  it('handles loading state', () => {
    render(<[ComponentName] {...mockProps} isLoading />);
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
});
```

### 출력 형식

```markdown
## 생성된 React 컴포넌트

### 파일 구조
```
src/components/[ComponentName]/
├── index.ts              # 내보내기 인덱스
├── [ComponentName].tsx   # 메인 컴포넌트
├── [ComponentName].types.ts  # TypeScript 타입
└── [ComponentName].test.tsx  # 테스트 파일
```

### 주요 기능
- ✅ TypeScript 완전 지원
- ✅ TailwindCSS + daisyUI 스타일링
- ✅ React Hook Form 통합 (폼 컴포넌트)
- ✅ 다국어 지원 (i18next)
- ✅ 접근성 최적화
- ✅ 테스트 코드 포함

### 사용 방법
```typescript
import { [ComponentName] } from '@/components/[ComponentName]';

// 컴포넌트 사용 예시
<[ComponentName]
  prop1="value1"
  prop2="value2"
  onAction={handleAction}
/>
```

### 다음 단계
1. 스타일 커스터마이징
2. 추가 기능 구현
3. Storybook 스토리 작성 (선택사항)
4. 통합 테스트 추가

### 테스트 실행
```bash
npm test -- [ComponentName]
```
```

사용자의 요청 "$ARGUMENTS"에 따라 적절한 React 컴포넌트를 생성하고 프로젝트 아키텍처에 맞게 최적화하세요.