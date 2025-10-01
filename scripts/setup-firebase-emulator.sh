#!/bin/bash
# Firebase 에뮬레이터 로컬 환경 설정 스크립트

set -e

echo "🔥 Firebase 에뮬레이터 로컬 환경 설정 시작..."

# .env 파일 확인
if [ ! -f .env ]; then
    echo "❌ .env 파일이 없습니다. .env.example을 복사하세요:"
    echo "   cp .env.example .env"
    exit 1
fi

echo "📝 .env 파일에 Firebase 에뮬레이터 설정 추가 중..."

# Firebase 에뮬레이터 설정이 이미 있는지 확인
if grep -q "^FIREBASE_USE_EMULATOR=" .env; then
    echo "⚠️  FIREBASE_USE_EMULATOR 설정이 이미 존재합니다."
    read -p "덮어쓰시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ 설정을 취소했습니다."
        exit 1
    fi

    # 기존 설정 제거
    sed -i.bak '/^FIREBASE_USE_EMULATOR=/d' .env
    sed -i.bak '/^FIREBASE_AUTH_EMULATOR_HOST=/d' .env
    sed -i.bak '/^VITE_FIREBASE_USE_EMULATOR=/d' .env
    sed -i.bak '/^VITE_FIREBASE_AUTH_EMULATOR_HOST=/d' .env
fi

# Firebase 에뮬레이터 설정 추가
cat >> .env << 'EOF'

# Firebase Emulator Settings (Local Development Only)
FIREBASE_USE_EMULATOR=true
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099

# Vite 환경변수
VITE_FIREBASE_USE_EMULATOR="${FIREBASE_USE_EMULATOR}"
VITE_FIREBASE_AUTH_EMULATOR_HOST="${FIREBASE_AUTH_EMULATOR_HOST}"
EOF

echo "✅ .env 파일에 Firebase 에뮬레이터 설정 추가 완료"

# Laravel 설정 캐시 클리어
echo "🔄 Laravel 설정 캐시 클리어 중..."
php artisan config:clear
echo "✅ Laravel 설정 캐시 클리어 완료"

echo ""
echo "🎉 설정 완료! 다음 단계를 진행하세요:"
echo ""
echo "1. Firebase 에뮬레이터 시작:"
echo "   firebase emulators:start"
echo ""
echo "2. 프론트엔드 개발 서버 시작:"
echo "   npm run dev"
echo ""
echo "3. 에뮬레이터 UI 접속:"
echo "   http://127.0.0.1:4000"
echo ""
echo "📖 자세한 사항은 docs/firebase/emulator-setup.md를 참고하세요."
