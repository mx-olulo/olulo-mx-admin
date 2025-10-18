#!/bin/bash
# Conductor Workspace Setup Script for olulo-mx-admin
# Laravel 12 + Filament 4 + Inertia.js (React) + Firebase Authentication

set -e

echo "🚀 Conductor Workspace Setup 시작..."
echo ""

# ============================================
# 1. 환경 검증 (Fail-Fast)
# ============================================
echo "📋 1/7 환경 검증 중..."

# PHP 버전 확인
if ! command -v php &> /dev/null; then
    echo "❌ PHP가 설치되어 있지 않습니다."
    echo "   brew install php@8.2 를 실행하세요."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
if [[ $(echo "$PHP_VERSION < 8.2" | bc) -eq 1 ]]; then
    echo "❌ PHP 8.2 이상이 필요합니다. (현재: $PHP_VERSION)"
    exit 1
fi
echo "✅ PHP $PHP_VERSION 확인됨"

# Composer 확인
if ! command -v composer &> /dev/null; then
    echo "❌ Composer가 설치되어 있지 않습니다."
    echo "   https://getcomposer.org/download/ 를 참고하세요."
    exit 1
fi
echo "✅ Composer $(composer --version --no-ansi | head -n1 | awk '{print $3}') 확인됨"

# Redis PHP 익스텐션 확인
if ! php -m | grep -q "^redis$"; then
    echo "❌ Redis PHP 익스텐션이 설치되어 있지 않습니다."
    echo "   pecl install redis 를 실행하세요."
    echo "   또는 brew install php@8.2 (Redis 익스텐션 포함)"
    exit 1
fi
echo "✅ Redis PHP 익스텐션 확인됨"

# pnpm 확인
if ! command -v pnpm &> /dev/null; then
    echo "❌ pnpm이 설치되어 있지 않습니다."
    echo "   npm install -g pnpm 또는 brew install pnpm 을 실행하세요."
    exit 1
fi
echo "✅ pnpm $(pnpm --version) 확인됨"

# PostgreSQL 확인
if ! command -v psql &> /dev/null; then
    echo "⚠️  PostgreSQL 클라이언트가 설치되어 있지 않습니다."
    echo "   brew install postgresql@16 을 실행하세요."
    echo "   (서버는 실행 중이어야 합니다)"
fi

# Redis 확인
if ! command -v redis-cli &> /dev/null; then
    echo "⚠️  Redis 클라이언트가 설치되어 있지 않습니다."
    echo "   brew install redis 를 실행하세요."
    echo "   (서버는 실행 중이어야 합니다)"
fi

echo ""

# ============================================
# 2. 환경변수 설정
# ============================================
echo "📝 2/7 환경변수 설정 중..."

if [ ! -f .env ]; then
    if [ -n "$CONDUCTOR_ROOT_PATH" ] && [ -f "$CONDUCTOR_ROOT_PATH/.env" ]; then
        echo "📎 루트 저장소의 .env 파일을 symlink로 연결합니다..."
        ln -s "$CONDUCTOR_ROOT_PATH/.env" .env
        echo "✅ .env symlink 생성 완료"
    else
        echo "📄 .env.example을 복사하여 .env 파일 생성 중..."
        cp .env.example .env
        echo "⚠️  .env 파일을 편집하여 필수 환경변수를 설정해야 합니다:"
        echo "   - DB_DATABASE, DB_USERNAME, DB_PASSWORD"
        echo "   - FIREBASE_* 설정"
        echo "   - REDIS_HOST (필요 시)"
    fi
else
    echo "✅ .env 파일이 이미 존재합니다"
fi

echo ""

# ============================================
# 3. Firebase 에뮬레이터 확인 및 시작
# ============================================
echo "🔥 3/7 Firebase 에뮬레이터 확인 중..."

FIREBASE_PORT=9099

# 포트 9099가 사용 중인지 확인
if lsof -Pi :$FIREBASE_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "✅ Firebase 에뮬레이터가 이미 실행 중입니다 (포트 $FIREBASE_PORT)"
    FIREBASE_RUNNING=true
else
    echo "⚠️  Firebase 에뮬레이터가 실행되지 않았습니다"
    FIREBASE_RUNNING=false

    # Firebase CLI 확인
    if command -v firebase &> /dev/null; then
        echo "🚀 Firebase 에뮬레이터를 백그라운드로 시작합니다..."

        # 에뮬레이터를 백그라운드로 실행 (루트 디렉토리에서)
        if [ -n "$CONDUCTOR_ROOT_PATH" ] && [ -f "$CONDUCTOR_ROOT_PATH/firebase.json" ]; then
            cd "$CONDUCTOR_ROOT_PATH"
            nohup firebase emulators:start > /dev/null 2>&1 &
            FIREBASE_PID=$!
            cd - > /dev/null

            # 에뮬레이터가 시작될 때까지 대기
            echo "⏳ Firebase 에뮬레이터 시작 대기 중..."
            sleep 5

            if lsof -Pi :$FIREBASE_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
                echo "✅ Firebase 에뮬레이터 시작 완료 (PID: $FIREBASE_PID)"
                echo "   UI: http://127.0.0.1:4000"
            else
                echo "⚠️  Firebase 에뮬레이터 시작 실패. 수동으로 시작하세요:"
                echo "   firebase emulators:start"
            fi
        else
            echo "⚠️  firebase.json을 찾을 수 없습니다. 수동으로 시작하세요:"
            echo "   firebase emulators:start"
        fi
    else
        echo "⚠️  Firebase CLI가 설치되어 있지 않습니다."
        echo "   npm install -g firebase-tools 를 실행하세요."
        echo "   또는 다른 워크스페이스에서 에뮬레이터를 시작하세요."
    fi
fi

# .env에 에뮬레이터 설정 추가 (없는 경우)
if ! grep -q "^FIREBASE_USE_EMULATOR=" .env 2>/dev/null; then
    echo "📝 .env에 Firebase 에뮬레이터 설정 추가 중..."
    cat >> .env << 'EOF'

# Firebase Emulator Settings (Local Development)
FIREBASE_USE_EMULATOR=true
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
VITE_FIREBASE_USE_EMULATOR=true
VITE_FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
EOF
    echo "✅ Firebase 에뮬레이터 설정 추가 완료"
fi

echo ""

# ============================================
# 4. Composer 의존성 설치
# ============================================
echo "📦 4/7 Composer 의존성 설치 중..."

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
    echo "✅ Composer 패키지 설치 완료"
else
    echo "✅ vendor/ 디렉토리가 이미 존재합니다 (설치 건너뜀)"
fi

echo ""

# ============================================
# 5. pnpm 의존성 설치
# ============================================
echo "📦 5/7 pnpm 의존성 설치 중..."

if [ ! -d node_modules ]; then
    pnpm install
    echo "✅ pnpm 패키지 설치 완료"
else
    echo "✅ node_modules/ 디렉토리가 이미 존재합니다 (설치 건너뜀)"
fi

echo ""

# ============================================
# 6. Laravel 초기화
# ============================================
echo "🔧 6/7 Laravel 애플리케이션 초기화 중..."

# APP_KEY 생성 (없는 경우)
if ! grep -q "^APP_KEY=.\+" .env 2>/dev/null; then
    echo "🔑 Laravel 애플리케이션 키 생성 중..."
    php artisan key:generate --ansi
    echo "✅ APP_KEY 생성 완료"
else
    echo "✅ APP_KEY가 이미 설정되어 있습니다"
fi

# 설정 캐시 클리어
echo "🔄 Laravel 설정 캐시 클리어 중..."
php artisan config:clear

# 데이터베이스 연결 테스트
echo "🔌 데이터베이스 연결 테스트 중..."
if php artisan db:show --ansi 2>/dev/null; then
    echo "✅ 데이터베이스 연결 성공"

    # 마이그레이션 실행
    echo "🗄️  데이터베이스 마이그레이션 실행 중..."
    php artisan migrate --force --ansi
    echo "✅ 마이그레이션 완료"

    # 시딩 실행
    echo "🌱 데이터베이스 시딩 실행 중..."
    if php artisan db:seed --force --ansi; then
        echo "✅ 시딩 완료"
    else
        echo "⚠️  시딩 실패 (선택사항이므로 계속 진행합니다)"
    fi
else
    echo "⚠️  데이터베이스 연결 실패"
    echo "   .env 파일의 DB_* 설정을 확인하세요:"
    echo "   - DB_CONNECTION=pgsql"
    echo "   - DB_HOST=127.0.0.1"
    echo "   - DB_PORT=5432"
    echo "   - DB_DATABASE=olulo_mx_admin"
    echo "   - DB_USERNAME=postgres"
    echo "   - DB_PASSWORD=..."
    echo ""
    echo "   PostgreSQL이 실행 중인지 확인하세요:"
    echo "   brew services start postgresql@16"
fi

echo ""

# ============================================
# 7. 최종 확인
# ============================================
echo "✅ 7/7 설정 완료!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Workspace 설정이 완료되었습니다!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 다음 단계:"
echo "   1. Conductor의 'Run' 버튼을 클릭하여 개발 서버를 시작하세요"
echo "   2. 또는 터미널에서: composer run dev"
echo ""
echo "🔗 유용한 링크:"
echo "   - 애플리케이션: https://admin.dev.olulo.com.mx"
echo "   - Firebase 에뮬레이터 UI: http://127.0.0.1:4000"
echo "   - Filament Admin: /admin"
echo "   - Laravel Nova: /nova"
echo ""
echo "📖 문서:"
echo "   - README.md"
echo "   - docs/"
echo ""
