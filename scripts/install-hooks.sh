#!/bin/bash

# Olulo MX - Git Hooks 설치 스크립트
# pre-commit 훅을 .git/hooks/에 설치합니다.

HOOK_DIR=".git/hooks"
HOOK_FILE="$HOOK_DIR/pre-commit"

echo "🔧 Installing git hooks..."

# pre-commit 훅 생성
cat > "$HOOK_FILE" << 'EOF'
#!/bin/bash

# Olulo MX - Pre-commit Hook
# 코드 품질 검사: Rector, Pint, PHPStan

set -e

echo "🔍 Running code quality checks..."

# Rector 검사
echo "📦 Running Rector..."
if ! composer rector:check; then
    echo "❌ Rector found issues. Please run 'composer rector' to fix them."
    exit 1
fi

# Pint 검사
echo "✨ Running Pint..."
if ! composer pint:check; then
    echo "❌ Pint found formatting issues. Please run 'composer pint' to fix them."
    exit 1
fi

# PHPStan 검사
echo "🔬 Running PHPStan..."
if ! composer phpstan; then
    echo "❌ PHPStan found issues. Please fix them before committing."
    exit 1
fi

echo "✅ All quality checks passed!"
exit 0
EOF

# 실행 권한 부여
chmod +x "$HOOK_FILE"

echo "✅ Git hooks installed successfully!"
echo "📝 Hook location: $HOOK_FILE"
