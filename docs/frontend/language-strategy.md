# 언어 처리 전략 (향후 구현)

## 지원 언어
- 한국어 (ko)
- 스페인어 (es-MX) - 기본값
- 영어 (en)

## 기본 방향
- Laravel 기본 `App::setLocale()` 사용
- react-i18next로 프론트엔드 번역
- 번역 파일: `lang/{locale}/messages.json`

## 우선순위
1. 세션/쿠키 저장된 언어
2. 사용자 프로필 설정
3. Accept-Language 헤더
4. 기본값 (es-MX)

## 향후 작업
- LocaleMiddleware 구현 (리소스 생성 후)
- react-i18next 통합
- 번역 파일 작성
- 언어 전환 UI
