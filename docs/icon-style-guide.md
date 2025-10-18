# 아이콘 스타일 가이드

이 문서는 고객앱 아이콘 컴포넌트의 스타일 규칙을 정의합니다. 모든 코드는 영어, 문서는 한국어로 작성합니다.

## 원칙
- 하나의 파일에는 하나의 export만 유지합니다.
- 활성 상태는 시각적 스타일과 접근성 모두 반영합니다.
- 색상은 `currentColor` 전략으로 통일합니다.

## 구현 규칙
- `IconBase`는 다음을 제공합니다:
  - `data-active`: 'true' | 'false'
  - `aria-current`: 활성일 때 'page'
  - 래퍼 요소에 `className` 전달 가능
- 모든 아이콘 path/line은 색상 값을 직접 넣지 말고 `currentColor`를 사용합니다.
  - 예) `<path fill="currentColor" />`, `<path stroke="currentColor" />`
- 활성/비활성 색상은 상위 컨테이너에서 제어합니다.
  - 예) `className="text-gray-500 data-[active=true]:text-emerald-500"`

## Tailwind 예시
```tsx
<IconBase
  active={isActive}
  className="text-gray-500 data-[active=true]:text-emerald-500"
>
  {/* child SVG paths with currentColor */}
</IconBase>
```

## 접근성
- 활성 상태일 때 `aria-current="page"`가 설정됩니다.
- 추가 접근성 속성이 필요할 경우, `IconBase`에 전달할 수 있도록 인터페이스 확장을 검토합니다.

## 금지 사항
- 개별 아이콘 파일에서 직접 색상 hex를 하드코딩하지 않습니다.
- 한 파일에 여러 export를 두지 않습니다.

## 마이그레이션 체크리스트
- [ ] 모든 아이콘이 `currentColor` 사용
- [ ] 활성 스타일을 `data-active` 기반으로 제어
- [ ] 배럴(`icons/index.ts`)은 default export만 재노출
