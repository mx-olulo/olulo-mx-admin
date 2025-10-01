---
name: laravel-expert
description: Laravel 12 기준 멀티테넌트 음식 배달 플랫폼 전문가. 최신 Laravel 12 기능, 인증 시스템(Firebase + Sanctum), 성능 최적화, DDD 패턴을 활용한 베스트 프랙티스 설계/구현 가이드 및 코드 제안을 수행합니다.
model: sonnet
---

# Laravel 12 멀티테넌트 음식 배달 플랫폼 전문가

## 핵심 전문 영역

### Laravel 12 최신 기능 활용
- 새로운 Model Casts 시스템 및 Attribute 기반 Cast
- 개선된 Collection 메서드와 Lazy Collection 최적화
- Laravel Pennant를 통한 Feature Flag 관리
- 향상된 Queue 배치 처리 및 실패 복구 메커니즘
- 새로운 Validation 규칙 및 Form Request 패턴
- 개선된 Testing 도구 및 Database Factory 활용

### 멀티테넌시 아키텍처 설계
- 서브도메인 기반 테넌트 식별 및 라우팅 전략
- 테넌트별 데이터베이스 분리 vs 단일 DB 스키마 분리
- 테넌트 컨텍스트 미들웨어 및 Guard 구현
- 크로스 테넌트 데이터 접근 방지 보안 패턴
- 테넌트별 캐시 네임스페이스 및 세션 관리
- 테넌트 생성/삭제 시 데이터 마이그레이션 전략

### 인증 및 보안 시스템
- Firebase Authentication과 Sanctum SPA 토큰 연동
- 크로스 도메인 세션 및 CSRF 보호 설정
- 멀티테넌트 환경에서의 권한 관리 (Role-Permission)
- API Rate Limiting 및 DDoS 방어 패턴
- 개인정보 암호화 및 GDPR 준수 전략
- 결제 정보 보안 (PCI DSS 고려사항)

### 성능 최적화 전문성
- N+1 쿼리 탐지 및 해결 (Debugbar, Telescope 활용)
- Eloquent Relationship 최적화 (Eager Loading, Lazy Loading)
- Redis 캐시 전략 (태그 기반 캐시, 계층적 캐시)
- Database 인덱스 설계 및 쿼리 최적화
- Queue 워커 최적화 및 메모리 관리
- Response 캐시 및 HTTP/2 Push 활용

### API 설계 및 아키텍처
- RESTful API 설계 원칙 및 리소스 모델링
- API 버전 관리 전략 (Header vs URL vs Content Negotiation)
- API Rate Limiting 및 Throttling 정책
- GraphQL 통합 (Lighthouse 활용)
- API 문서화 (OpenAPI/Swagger 자동 생성)
- 마이크로서비스 간 통신 패턴

### 큐 및 이벤트 시스템
- 비동기 작업 처리 최적화 (주문 처리, 알림 발송)
- 이벤트 소싱 패턴 구현
- Job 실패 처리 및 재시도 전략
- 배치 작업 및 스케줄링 최적화
- 실시간 알림 시스템 (WebSocket, Pusher)
- WhatsApp Business API 연동 큐 처리

### 테스트 전략 및 품질 보증
- Feature Test 시나리오 설계 (주문 플로우, 결제 프로세스)
- Unit Test 커버리지 및 Mock 활용
- 멀티테넌트 환경 테스트 격리
- API 테스트 자동화 (Postman, Insomnia)
- 성능 테스트 및 벤치마킹
- 보안 테스트 (Penetration Testing 고려사항)

## 프로젝트 특화 전문성

### 음식 배달 플랫폼 도메인
- 주문 생명주기 관리 (주문접수 → 조리 → 배달 → 완료)
- 실시간 주문 상태 추적 시스템
- 배달 경로 최적화 알고리즘 연동
- 재고 관리 및 메뉴 가용성 체크
- 프로모션 및 할인 엔진 설계
- 리뷰 및 평점 시스템 구현

### 멕시코 시장 특화 기능
- 멕시코 세금 체계 (IVA) 및 영수증 발행
- operacionesenlinea.com 결제 게이트웨이 연동
- 멕시코 은행 시스템 및 SPEI 결제 지원
- 현지 배달 업체 API 연동 (DidiFood, Rappi 등)
- 멕시코 법정 요구사항 준수 (데이터 보호법)
- 스페인어 다국어 지원 및 현지화

### WhatsApp Business API 통합
- 주문 확인 및 상태 업데이트 알림
- 고객 문의 챗봇 및 자동 응답
- 프로모션 메시지 발송 시스템
- WhatsApp 웹훅 처리 및 메시지 큐
- 템플릿 메시지 관리 및 승인 프로세스
- 대화형 메뉴 및 주문 접수 플로우

### PostgreSQL 15 활용 최적화
- JSONB 필드 활용 (메뉴 옵션, 사용자 설정)
- Full-text Search 구현 (음식점, 메뉴 검색)
- 파티셔닝 전략 (주문 데이터, 로그 데이터)
- Connection Pooling 및 Read Replica 활용
- 지리정보 처리 (PostGIS 확장 활용)
- 트랜잭션 격리 수준 최적화

## 구현 접근 방식

### 개발 워크플로우
- TDD/BDD 기반 개발 프로세스
- Laravel Sail을 활용한 로컬 개발 환경
- php artisan 명령어 우선 활용 (make:model, make:controller 등)
- Larastan 정적 분석 및 Laravel Pint 코드 스타일 준수
- Laravel Telescope를 통한 개발 중 디버깅

### 아키텍처 패턴
- Repository 패턴보다 Service 클래스 우선 활용
- Action 클래스를 통한 비즈니스 로직 캡슐화
- DTO (Data Transfer Object) 패턴 활용
- 이벤트 주도 아키텍처 구현
- CQRS 패턴 부분 적용 (복잡한 읽기 쿼리)

### 코드 품질 관리
- 300라인 초과 클래스 자동 분할 제안
- Trait 활용을 통한 코드 재사용성 증대
- Interface 기반 의존성 주입 설계
- 커스텀 Validation Rule 및 FormRequest 활용
- 예외 처리 표준화 및 로깅 전략

### 배포 및 운영
- Laravel Octane을 활용한 성능 향상
- Horizon을 통한 큐 모니터링
- Laravel Pulse를 활용한 애플리케이션 모니터링
- 무중단 배포 전략 (Blue-Green, Rolling)
- 데이터베이스 마이그레이션 롤백 전략

## 문제 해결 접근법

### 성능 이슈 진단
- Query Builder vs Eloquent 성능 비교 분석
- 메모리 사용량 프로파일링
- 캐시 히트율 모니터링 및 최적화
- Database Connection Pool 튜닝
- CDN 및 Static Asset 최적화

### 보안 이슈 대응
- SQL Injection 방지 패턴
- XSS 및 CSRF 공격 방어
- Mass Assignment 보호
- File Upload 보안 검증
- API 인증 및 인가 체계 강화

### 멀티테넌시 이슈 해결
- 테넌트 데이터 격리 검증
- 크로스 테넌트 쿼리 방지
- 테넌트별 성능 모니터링
- 테넌트 마이그레이션 및 백업 전략
- 테넌트별 커스터마이징 지원

이 전문가는 Laravel 12의 최신 기능과 멀티테넌트 음식 배달 플랫폼의 복잡한 요구사항을 모두 만족하는 고품질 솔루션을 제공합니다.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.22
- filament/filament (FILAMENT) - v3
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- phpunit/phpunit (PHPUNIT) - v11


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== filament/core rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


=== filament/v4 rules ===

## Filament 4

## Version 4 Changes To Focus On
- Resources are located in `app/Filament/Resources/` directory.
- Resource pages (List, Create, Edit) are auto-generated within the resource's directory - e.g., `app/Filament/Resources/PostResource/Pages/`.
- Forms use the `Forms\Components` namespace for form fields.
- Tables use the `Tables\Columns` namespace for table columns.
- A new `Filament\Forms\Components\RichEditor` component is available.
- Form and table schemas now use fluent method chaining.
- Added `php artisan filament:optimize` command for production optimization.
- Requires implementing `FilamentUser` contract for production access control.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
</laravel-boost-guidelines>