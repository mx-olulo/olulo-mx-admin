{{-- @CODE:AUTH-REDIRECT-001:UI | SPEC: SPEC-AUTH-REDIRECT-001.md | TEST: tests/Feature/Auth/RedirectTest.php --}}
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>테넌트 선택</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 24px;
            text-align: center;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }
        .tab-button {
            flex: 1;
            padding: 12px 24px;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
        }
        .tab-button:hover {
            color: #667eea;
        }
        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tenant-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .tenant-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        .tenant-name {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .empty-message {
            text-align: center;
            color: #6b7280;
            padding: 40px 20px;
        }
        .empty-message p {
            margin-bottom: 12px;
        }
        .info-text {
            color: #9ca3af;
            font-size: 14px;
        }
        .create-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 16px;
            transition: all 0.2s;
        }
        .create-button:hover {
            background: #5568d3;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .create-button-container {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>테넌트 선택</h2>

        <div class="tabs">
            <button class="tab-button active" data-tab="organization">Organization</button>
            <button class="tab-button" data-tab="store">Store</button>
            <button class="tab-button" data-tab="brand">Brand</button>
        </div>

        {{-- Organization 탭 --}}
        <div class="tab-content active" id="organization-tab">
            @forelse($organizations as $org)
                <form action="/tenant/select" method="POST" style="margin: 0;">
                    @csrf
                    <input type="hidden" name="tenant_type" value="organization">
                    <input type="hidden" name="tenant_id" value="{{ $org->id }}">
                    <button type="submit" class="tenant-card" style="width: 100%; border: none; background: none; text-align: left;">
                        <div class="tenant-name">{{ $org->name }}</div>
                    </button>
                </form>
            @empty
                <div class="empty-message">
                    <p>소속된 Organization이 없습니다.</p>
                </div>
            @endforelse

            <div class="create-button-container">
                <a href="/org/new" class="create-button">+ Organization 생성</a>
            </div>
        </div>

        {{-- Store 탭 --}}
        <div class="tab-content" id="store-tab">
            @forelse($stores as $store)
                <form action="/tenant/select" method="POST" style="margin: 0;">
                    @csrf
                    <input type="hidden" name="tenant_type" value="store">
                    <input type="hidden" name="tenant_id" value="{{ $store->id }}">
                    <button type="submit" class="tenant-card" style="width: 100%; border: none; background: none; text-align: left;">
                        <div class="tenant-name">{{ $store->name }}</div>
                    </button>
                </form>
            @empty
                <div class="empty-message">
                    <p>소속된 Store가 없습니다.</p>
                </div>
            @endforelse

            <div class="create-button-container">
                <a href="/store/new" class="create-button">+ Store 생성</a>
            </div>
        </div>

        {{-- Brand 탭 (생성 버튼 없음 - 핵심 제약) --}}
        <div class="tab-content" id="brand-tab">
            @forelse($brands as $brand)
                <form action="/tenant/select" method="POST" style="margin: 0;">
                    @csrf
                    <input type="hidden" name="tenant_type" value="brand">
                    <input type="hidden" name="tenant_id" value="{{ $brand->id }}">
                    <button type="submit" class="tenant-card" style="width: 100%; border: none; background: none; text-align: left;">
                        <div class="tenant-name">{{ $brand->name }}</div>
                    </button>
                </form>
            @empty
                <div class="empty-message">
                    <p>소속된 Brand가 없습니다.</p>
                    <p class="info-text">Brand는 Organization 패널에서 생성할 수 있습니다.</p>
                </div>
            @endforelse

            {{-- Brand 생성 버튼 없음 (Organization 패널에서만 생성 가능) --}}
        </div>
    </div>

    <script>
        // 탭 전환 기능
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // 모든 탭 버튼 비활성화
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                // 모든 탭 콘텐츠 숨기기
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });

                // 클릭한 탭 활성화
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
    </script>
</body>
</html>
