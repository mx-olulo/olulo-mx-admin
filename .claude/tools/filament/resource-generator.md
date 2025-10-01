# Filament 4 리소스 생성기

Filament 4 관리자 패널용 리소스를 프로젝트 규칙에 맞게 자동 생성하는 도구입니다.

## 사용법
```
/tools/filament:resource-generator [모델명] [옵션]
```

## 기본 동작

당신은 Filament 4 전문가로서 Laravel 모델을 기반으로 완전한 Filament 리소스를 생성합니다. 매장 관리자가 사용할 관리 패널에 최적화된 리소스를 만들어야 합니다.

### 생성할 리소스 컴포넌트

1. **Resource 클래스** - 메인 리소스 정의
2. **CreatePage** - 생성 페이지
3. **EditPage** - 편집 페이지
4. **ListPage** - 목록 페이지
5. **ViewPage** - 상세 보기 페이지 (선택사항)
6. **RelationManager** - 관계 관리 (필요시)

### 프로젝트 특화 설정

#### 멀티테넌시 고려사항
- 테넌트 스코핑 자동 적용
- 매장별 데이터 격리 보장
- 권한 기반 접근 제어

#### 멕시코 현지화 지원
- CURP/RFC 필드 특별 처리
- 멕시코 페소(MXN) 통화 포맷
- 스페인어 레이블 및 도움말

#### 전자상거래 최적화
- 주문/상품 관리 특화 컴포넌트
- 실시간 상태 업데이트
- 이미지 업로드 및 관리

### 실행 프로세스

사용자 요청 "$ARGUMENTS"을 분석하여:

1. **모델 분석**
   - 모델 파일 존재 확인
   - 테이블 스키마 분석
   - 관계(Relationship) 파악
   - 필드 타입 및 제약조건 확인

2. **리소스 설계**
   - Form 필드 자동 생성
   - Table 컬럼 최적 배치
   - 필터(Filter) 구성
   - 액션(Action) 정의

3. **코드 생성**
   ```bash
   php artisan make:filament-resource {ModelName} --generate
   ```

4. **커스터마이징**
   - 프로젝트 규칙 적용
   - 한국어/스페인어 번역 키 추가
   - 테넌트 스코핑 로직 삽입
   - 권한 검사 코드 추가

### 주요 기능별 생성 패턴

#### Form 구성
```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // 기본 필드들
            TextInput::make('name')
                ->label(__('filament.fields.name'))
                ->required()
                ->maxLength(255),

            // 멕시코 특화 필드
            TextInput::make('rfc')
                ->label(__('filament.fields.rfc'))
                ->mask('AAAA999999AAA')
                ->rule('rfc_validation'),

            // 통화 필드
            TextInput::make('price')
                ->label(__('filament.fields.price'))
                ->numeric()
                ->prefix('$')
                ->step(0.01),

            // 관계 필드
            Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable()
                ->preload(),
        ]);
}
```

#### Table 구성
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('id')
                ->label(__('filament.columns.id'))
                ->sortable(),

            TextColumn::make('name')
                ->label(__('filament.columns.name'))
                ->searchable()
                ->sortable(),

            TextColumn::make('price')
                ->label(__('filament.columns.price'))
                ->money('MXN')
                ->sortable(),

            BadgeColumn::make('status')
                ->label(__('filament.columns.status'))
                ->enum([
                    'active' => __('filament.status.active'),
                    'inactive' => __('filament.status.inactive'),
                ]),

            TextColumn::make('created_at')
                ->label(__('filament.columns.created_at'))
                ->dateTime('d/M/Y H:i')
                ->sortable(),
        ])
        ->filters([
            SelectFilter::make('status')
                ->label(__('filament.filters.status'))
                ->options([
                    'active' => __('filament.status.active'),
                    'inactive' => __('filament.status.inactive'),
                ]),
        ])
        ->actions([
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->bulkActions([
            DeleteBulkAction::make(),
        ]);
}
```

#### 권한 및 스코핑
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->scoped(); // 테넌트 스코핑 적용
}

public static function canViewAny(): bool
{
    return auth()->user()->can('viewAny', static::getModel());
}
```

### 특별 처리 패턴

#### 주문 관리 리소스
- 주문 상태 실시간 업데이트
- 결제 상태 표시
- WhatsApp 알림 발송 액션

#### 상품 관리 리소스
- 이미지 다중 업로드
- 카테고리/옵션 관리
- 재고 추적 시스템

#### 고객 관리 리소스
- CURP/RFC 검증
- 주문 히스토리 관계 매니저
- 선호도 분석 위젯

### 생성 후 검증

1. **코드 품질 검증**
   ```bash
   ./vendor/bin/pint app/Filament/Resources/
   ./vendor/bin/phpstan analyse app/Filament/Resources/
   ```

2. **기능 테스트**
   - CRUD 동작 확인
   - 권한 검사 테스트
   - 테넌트 격리 검증

3. **UI/UX 검토**
   - 반응형 레이아웃 확인
   - 다국어 표시 검증
   - 접근성 준수 여부

### 출력 형식

```markdown
## 생성된 Filament 리소스

### 파일 목록
- `app/Filament/Resources/{ModelName}Resource.php`
- `app/Filament/Resources/{ModelName}Resource/Pages/Create{ModelName}.php`
- `app/Filament/Resources/{ModelName}Resource/Pages/Edit{ModelName}.php`
- `app/Filament/Resources/{ModelName}Resource/Pages/List{ModelName}s.php`

### 주요 기능
- ✅ CRUD 기본 기능
- ✅ 테넌트 스코핑
- ✅ 권한 기반 접근 제어
- ✅ 멕시코 현지화 지원
- ✅ 반응형 UI

### 다음 단계
1. 번역 키 추가: `lang/es/filament.php`
2. 권한 정책 검토: `app/Policies/{ModelName}Policy.php`
3. 관계 매니저 추가 (필요시)
4. 커스텀 액션 구현 (필요시)

### 테스트 방법
```bash
# 기능 테스트
php artisan test --filter={ModelName}Resource

# 브라우저 테스트
# /admin/{resource-name} 접속하여 CRUD 동작 확인
```
```

사용자의 요청 "$ARGUMENTS"에 따라 적절한 Filament 리소스를 생성하고 프로젝트 요구사항에 맞게 커스터마이징하세요.