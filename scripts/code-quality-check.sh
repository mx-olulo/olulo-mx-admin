#!/bin/bash

# Olulo MX - 코드 품질 검증 통합 스크립트
# Rector, Pint, PHPStan을 순차적으로 실행하여 코드 품질을 검증합니다.

set -e  # 에러 발생 시 스크립트 중단

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 로그 함수
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 도움말 출력
show_help() {
    echo "Olulo MX 코드 품질 검증 스크립트"
    echo ""
    echo "사용법: $0 [옵션]"
    echo ""
    echo "옵션:"
    echo "  --dry-run     Rector와 Pint를 dry-run 모드로 실행 (변경 없이 확인만)"
    echo "  --rector      Rector만 실행"
    echo "  --pint        Pint만 실행"
    echo "  --phpstan     PHPStan만 실행"
    echo "  --fix         Rector와 Pint를 실행하여 코드 수정 (기본값)"
    echo "  --help        이 도움말 표시"
    echo ""
    echo "예시:"
    echo "  $0                 # 모든 도구 실행 (수정 모드)"
    echo "  $0 --dry-run       # 모든 도구 실행 (확인 모드)"
    echo "  $0 --phpstan       # PHPStan만 실행"
}

# 실행 모드 설정
DRY_RUN=false
RUN_RECTOR=true
RUN_PINT=true
RUN_PHPSTAN=true

# 인자 파싱
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --rector)
            RUN_PINT=false
            RUN_PHPSTAN=false
            shift
            ;;
        --pint)
            RUN_RECTOR=false
            RUN_PHPSTAN=false
            shift
            ;;
        --phpstan)
            RUN_RECTOR=false
            RUN_PINT=false
            shift
            ;;
        --fix)
            DRY_RUN=false
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            log_error "알 수 없는 옵션: $1"
            show_help
            exit 1
            ;;
    esac
done

# 스크립트 시작
log_info "======================================"
log_info "Olulo MX 코드 품질 검증 시작"
log_info "======================================"
echo ""

# 1. Rector 실행
if [ "$RUN_RECTOR" = true ]; then
    log_info "1단계: Rector 실행 중..."
    if [ "$DRY_RUN" = true ]; then
        log_warning "Dry-run 모드: Rector는 변경 사항만 표시합니다"
        if vendor/bin/rector process --dry-run; then
            log_success "Rector 검증 완료 (변경 사항 없음)"
        else
            log_warning "Rector가 개선 가능한 코드를 발견했습니다"
        fi
    else
        log_info "Rector로 코드 리팩토링 중..."
        if vendor/bin/rector process; then
            log_success "Rector 리팩토링 완료"
        else
            log_error "Rector 실행 중 오류 발생"
            exit 1
        fi
    fi
    echo ""
fi

# 2. Pint 실행
if [ "$RUN_PINT" = true ]; then
    log_info "2단계: Pint 실행 중..."
    if [ "$DRY_RUN" = true ]; then
        log_warning "Dry-run 모드: Pint는 문제만 표시합니다"
        if vendor/bin/pint --test; then
            log_success "Pint 검증 완료 (스타일 이슈 없음)"
        else
            log_warning "Pint가 스타일 이슈를 발견했습니다"
        fi
    else
        log_info "Pint로 코드 스타일 수정 중..."
        if vendor/bin/pint; then
            log_success "Pint 코드 스타일 수정 완료"
        else
            log_error "Pint 실행 중 오류 발생"
            exit 1
        fi
    fi
    echo ""
fi

# 3. PHPStan 실행
if [ "$RUN_PHPSTAN" = true ]; then
    log_info "3단계: PHPStan 정적 분석 실행 중..."
    if php -d memory_limit=-1 vendor/bin/phpstan analyse; then
        log_success "PHPStan 정적 분석 완료 (에러 없음)"
    else
        log_error "PHPStan에서 에러를 발견했습니다"
        exit 1
    fi
    echo ""
fi

# 완료 메시지
echo ""
log_success "======================================"
log_success "모든 코드 품질 검증 완료!"
log_success "======================================"
echo ""

if [ "$DRY_RUN" = true ]; then
    log_info "Dry-run 모드로 실행되었습니다. 코드는 변경되지 않았습니다."
    log_info "실제 수정을 적용하려면 --fix 옵션 없이 실행하세요: $0"
else
    log_info "코드가 성공적으로 검증 및 수정되었습니다."
    log_info "변경 사항을 검토한 후 커밋하세요."
fi
