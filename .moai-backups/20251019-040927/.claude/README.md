# Claude Code Tools & Workflows for olulo-mx-admin

olulo-mx-admin 프로젝트를 위한 전문화된 Claude Code 도구 및 워크플로우 컬렉션입니다.

## 프로젝트 개요

**olulo-mx-admin**은 멕시코 전자상거래 플랫폼으로 다음 기술 스택을 사용합니다:
- **백엔드**: Laravel 12 + Filament 4 + Nova v5
- **프론트엔드**: React 19.1 PWA
- **인증**: Firebase + Sanctum SPA
- **결제**: operacionesenlinea.com (멕시코)
- **알림**: WhatsApp Business API
- **아키텍처**: 멀티테넌시 (서브도메인 기반)

## 디렉토리 구조

```
.claude/
├── agents/              # 15개 전문 에이전트
├── pipelines/           # 4개 최적화된 파이프라인
├── tools/              # 프로젝트 특화 도구들
│   ├── laravel/        # Laravel 12 전용 도구
│   ├── filament/       # Filament 4 관리자 도구
│   ├── nova/           # Nova v5 마스터 관리 도구
│   ├── react/          # React 19.1 컴포넌트 생성기
│   ├── mexico/         # 멕시코 현지화 도구
│   ├── payment/        # 결제 연동 도구
│   ├── whatsapp/       # WhatsApp Business API 도구
│   ├── tenancy/        # 멀티테넌시 관리 도구
│   ├── firebase/       # Firebase 인증 도구
│   ├── database/       # 데이터베이스 도구
│   ├── dev/           # 개발 효율성 도구
│   └── ops/           # 배포/운영 도구
└── workflows/          # 프로젝트 특화 워크플로우
    ├── ecommerce-order-flow.md
    ├── mexico-localization.md
    └── [기타 워크플로우들...]
```

## 주요 도구 (Tools)

### Laravel 개발 도구
| 도구 | 설명 | 사용법 |
|------|------|-------|
| `artisan-wrapper` | Laravel Artisan 명령어 래퍼 | `/tools/laravel:artisan-wrapper make:model Product -mfs` |
| `model-generator` | Model + Migration + Factory 생성 | `/tools/laravel:model-generator Order --tenant-scoped` |
| `boost-bootstrap` | Laravel Boost 보일러플레이트 적용 | `/tools/laravel:boost-bootstrap` |

### Filament 관리자 도구
| 도구 | 설명 | 사용법 |
|------|------|-------|
| `resource-generator` | Filament Resource 자동 생성 | `/tools/filament:resource-generator Order --crud` |
| `panel-setup` | 매장별 패널 설정 | `/tools/filament:panel-setup store-admin` |
| `widget-generator` | 대시보드 위젯 생성 | `/tools/filament:widget-generator SalesChart` |

### React PWA 도구
| 도구 | 설명 | 사용법 |
|------|------|-------|
| `component-generator` | React 컴포넌트 생성 | `/tools/react:component-generator MenuCard --type=business` |
| `pwa-setup` | PWA 설정 및 서비스 워커 | `/tools/react:pwa-setup` |
| `i18n-manager` | 다국어 번역 키 관리 | `/tools/react:i18n-manager add ko.menu.title` |

### 멕시코 현지화 도구
| 도구 | 설명 | 사용법 |
|------|------|-------|
| `curp-validator` | CURP 검증 시스템 구현 | `/tools/mexico:curp-validator` |
| `rfc-validator` | RFC 검증 시스템 구현 | `/tools/mexico:rfc-validator` |
| `tax-calculator` | 멕시코 세금 계산기 | `/tools/mexico:tax-calculator --iva-rate=16` |

## 주요 워크플로우 (Workflows)

### 1. 전자상거래 주문 플로우
```bash
/workflows:ecommerce-order-flow "QR 스캔부터 결제 완료까지 전체 주문 시스템 구현"
```

### 2. 멕시코 현지화 작업
```bash
/workflows:mexico-localization "CURP/RFC 검증, 세금 처리, 현지 규정 준수"
```

### 3. 매장 온보딩 플로우
```bash
/workflows:store-onboarding "새 매장 등록 및 설정 자동화"
```

## 서브에이전트 시스템

### 파이프라인 자동 선택
- **lightweight**: 파일 5개 이하, 100줄 이하 변경 (2분)
- **default**: 표준 개발 워크플로우 (5분)
- **optimized**: 멀티스택 변경, 조건부 실행 (8-12분)
- **extended**: 대규모 아키텍처 변경 (15-20분)

### 전문 에이전트 (15개)
- coordinator, architect, code-author, code-reviewer
- laravel-expert, filament-expert, nova-expert, react-expert
- database-expert, docs-reviewer, tailwind-expert, livewire-expert
- ux-expert, pm, github-expert

## 사용 예시

### 새 기능 개발
```bash
/workflows:ecommerce-order-flow "QR 코드 스캔부터 결제 완료까지"
/tools/react:component-generator MenuCard --with-cart-integration
```

### 멕시코 현지화
```bash
/workflows:mexico-localization "CURP/RFC 검증 및 세금 처리"
/tools/mexico:curp-validator
```

## 품질 관리

### 코드 품질 도구
```bash
/tools/laravel:pint-formatter
/tools/laravel:larastan-analyzer
/tools/dev:test-runner --coverage
```

## 참조
- CLAUDE 가이드: `../CLAUDE.md`
- 파이프라인 가이드: `pipelines/README.md`
- 프로젝트 문서: `../docs/`
