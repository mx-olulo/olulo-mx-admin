# Olulo MX Admin

Laravel 12 + Filament 4 기반 관리자와 React 19.1 고객 웹앱으로 오프라인 레스토랑의 주문을 통합 관리하는 프로젝트입니다. 인증은 FirebaseUI를 사용하며, 마스터 어드민은 Laravel Nova v5로 구성합니다. 멕시코 지역을 우선 지원하고, 다국어/다중 통화를 제공합니다.

## 문서
- 화이트페이퍼: `docs/whitepaper.md`
- 마일스톤 인덱스: `docs/milestones/README.md`
  - P1: `docs/milestones/project-1.md`
  - P2: `docs/milestones/project-2.md`
  - P3: `docs/milestones/project-3.md`
  - P4: `docs/milestones/project-4.md`
  - P5: `docs/milestones/project-5.md`
  - P6: `docs/milestones/project-6.md`
  - P7: `docs/milestones/project-7.md`
- 검수 체크 시스템
  - 안내: `docs/review/README.md`
  - 체크 파일: `docs/review/checks/*.md` (문서 변경 시 CI가 자동으로 "검토 필요: yes"로 갱신)
- 저장소 운영 규칙: `docs/repo/rules.md`
- CLAUDE 가이드: [CLAUDE.md](CLAUDE.md)
- CLAUDE 로컬 가이드: [CLAUDE.local.md](CLAUDE.local.md)
- CLAUDE 서브 에이전트 규격서: [docs/claude/subagents.md](docs/claude/subagents.md)
- CLAUDE 프로젝트 설정: [.claude/README.md](.claude/README.md)

## CI
- 워크플로: `.github/workflows/review-checks.yml`
  - 역할: `docs/**` 변경 시 해당 문서의 체크 파일을 자동으로 생성/갱신합니다.

## 기술 스택
- Backend: Laravel 12, PHP 8.2+, Filament 4.x, MySQL, Redis, Horizon
- Admin: Filament (매장), Laravel Nova v5 (마스터)
- Frontend: React 19.1 + Vite, Tailwind + daisyUI, react-i18next
- Auth: Firebase Authentication (FirebaseUI)
- Payments (MX): operacionesenlinea.com
- Notifications: WhatsApp (Meta Cloud API 권장, Twilio 대안)

## 로컬 개발(초안)
- Composer/NPM 설치 후 Laravel/Vite 기본 부트스트랩
- 환경값: `.env`에 Firebase/DB/Queue 설정
- 문서 참조: `docs/milestones/project-1.md`

## 라이선스
사내/프로젝트 정책에 따릅니다.
