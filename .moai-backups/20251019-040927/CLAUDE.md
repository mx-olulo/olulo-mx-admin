# CLAUDE Code 개발 가이드

본 문서는 Claude Code(이하 CLAUDE)가 본 저장소에서 개발/문서/리뷰를 수행할 때 따라야 할 공통 지침과 프롬프트 가드레일을 정의합니다.

## 목표
- 한국어(우리말)로 사고/응답
- Laravel 12 + Filament 4 + Nova v5 + React 19.1 구조에 맞는 변경안 제시
- 문서 우선(Documentation-first), PR 경유 머지 원칙 준수
- 서브 에이전트를 우선 활용한 품질 중심 개발

## 레포 컨텍스트
- 아키텍처/배경: `docs/whitepaper.md`
- 프로젝트1 상세: `docs/milestones/project-1.md`
- 인증/세션: `docs/auth.md`
- 환경/도메인: `docs/devops/environments.md`
- 저장소 운영 규칙: `docs/repo/rules.md`
- QA 체크리스트: `docs/qa/checklist.md`
- 테넌시 설계: `docs/tenancy/host-middleware.md`
- 관리자 설정: `docs/admin/filament-setup.md`, `docs/admin/nova-setup.md`
- 프런트엔드 설정: `docs/frontend/react-bootstrap.md`

## 작업 원칙
- 변경 전 맥락 파악: 관련 문서/코드 경로를 먼저 인용(`docs/...`, `.github/...`)
- 작은 단위 커밋/PR: 1 PR = 1 목적(atomic)
- 브랜치 전략 준수: `feature/*`, `chore/*`, `fix/*` 네이밍
- 메인/프로덕션 보호 준수: 직접 푸시 금지, PR 필수
- 문서→코드 순서: 설계 문서 갱신 후 구현 착수

## 프롬프트 가드레일(Claude가 스스로 준수)
- “반드시 한국어로 응답”
- “코드 변경은 항상 파일 경로를 명시하고, 작은 단위로 제안”
- “보호 브랜치에는 PR 경유”
- “보안/비밀 값은 커밋하지 않음(.env 등)”
- “테넌시/도메인/세션 정책을 임의 변경하지 않음(문서 준수)”
- “의존성 추가 시, `composer.json`/`package.json` 영향 및 배포 영향 명시”

## 반드시 지켜야 할 규칙 (Mandatory Rules)
1) 한 파일에 300라인 이상의 코드가 존재하는 경우, `trait`/`interface`/서비스 클래스 분리 등으로 코드 분할 및 리팩토링을 수행한다.
2) 데이터베이스/모델 수정·생성 및 컨트롤러 등 주요 PHP 클래스 생성은 `php artisan`(예: `make:model`, `make:migration`, `make:controller`)을 최우선으로 시도한다.
3) 변수/필드명은 일관되어야 한다. 새로운 이름을 만들기 전에 기존 유사 용도의 명칭이 있는지 반드시 확인한다. 이를 위해 `docs/` 문서와 `php artisan` 명령(예: `php artisan model:show` 등) 또는 IDE 검색으로 클래스/모델 구조를 확인한다.
4) 모든 커밋은 `larastan`과 `pint`를 통과한 경우에만 진행한다. (CI/로컬 모두 기준 준수)
5) 코드의 작성/수정은 전용 "서브 에이전트"를 생성하여 수행하고, 작성된 코드는 다른 서브 에이전트를 통하여 교차 검증한다. 상세 역할은 `docs/claude/subagents.md` 참조.
6) 프로젝트 초기 단계로 인해 실제 Laravel/React 코드가 아직 존재하지 않으므로, 코드 생성 시 문서 기준에 따라 기본 구조부터 순차적으로 구성한다.

## 산출물 형식
- 제안/요약은 Markdown 헤딩 + 불릿
- 코드 블록에는 언어 표기(php, js, md, yaml 등)
- 문서 간 교차참조 링크 삽입(문서 참조성 강화)

## PR 원칙
- PR 제목: `type(scope): summary` 또는 `chore: ...`
- 본문: 목적/변경점/체크리스트/참고 링크
- 리뷰 요청: CODEOWNERS 자동 할당 사용

## 프로젝트 1 특이사항
- 동일 루트(서브도메인) 기준 Sanctum SPA 세션
- 워크플로우 강화는 P1 진행 중 적용(문서의 이행 순서 준수)

### 보일러플레이트(laravel/boost) 적용 지침
- 목적: Laravel 12 기반 초기 스캐폴딩 표준화 및 생산성 향상
- 라이브러리: https://github.com/laravel/boost
- 적용 단계(Claude가 수행할 절차)
  1) 의존성 추가 제안: `composer require laravel/boost`
  2) upstream README를 참조해 초기 설정(필요 시 퍼블리시/설정 반영) 제안
  3) 저장소 규칙과 정합성 점검: `.editorconfig`, pint, 라우팅/디렉터리 구조 충돌 여부
  4) 전용 브랜치 생성: `chore/boost-bootstrap` → 작은 단위 커밋 → PR 생성
  5) PR 본문에 적용 범위/이유/영향/후속 TODO 명시(보안/세션/테넌시와의 비충돌 확인 포함)
  6) 리뷰/머지 완료 후 후속 작업(예: 스타일 규칙 통합, 스크립트 정비) 제안

## 개발 명령어 및 도구
- PHP/Laravel 도구
  - 코드 스타일: `pint --test` (검사), `pint` (수정)
  - 정적 분석: `php -d memory_limit=-1 vendor/bin/phpstan analyse`
  - Artisan 명령: `php artisan make:model`, `php artisan make:controller`, `php artisan model:show`
- 품질 검사 순서
  1) `composer validate` (composer.json 검증)
  2) `pint --test` (코드 스타일 검사)
  3) `larastan` 또는 `phpstan` (정적 분석)
  4) 필요시 `php -l` (구문 검사)

## 워크플로우 및 CI
- 현재 활성 워크플로우: `.github/workflows/review-checks.yml`
  - 트리거: `docs/**` 변경 시 `docs/review/checks/*.md` 자동 생성/갱신
  - 상태: "Update Review Checks" (production 브랜치 필수 체크)
- 계획된 강화: 빌드/테스트 워크플로우 추가 (프로젝트 1 내)
  - PHP 런타임, `composer validate`, `pint --test`, `larastan` 실행
  - 프런트엔드 포함 시: `npm/pnpm ci`, `vite build` 검증

## 서브 에이전트 시스템
- 위치: `.claude/agents/` (프로젝트 전용)
- 파이프라인: `.claude/pipelines/default.yaml`, `.claude/pipelines/extended.yaml`
- 사용 가능한 전문 에이전트:
  - code-author.md (코드 작성)
  - code-reviewer.md (코드 검토)
  - architect.md (아키텍처 설계)
  - laravel-expert.md (Laravel 전문)
  - filament-expert.md (Filament 전문)
  - nova-expert.md (Nova 전문)
  - react-expert.md (React 전문)
  - database-expert.md (DB 전문)
  - docs-reviewer.md (문서 검토)
  - tailwind-expert.md (Tailwind CSS)
  - livewire-expert.md (Livewire)
  - ux-expert.md (UX 전문)
  - pm.md (프로젝트 관리)
  - coordinator.md (조정자)

## 프로젝트 현재 상태
- 단계: 문서 중심 설계 완료, 코드 구현 준비 단계
- 기존 코드: 없음 (신규 프로젝트)
- 핵심 결정사항:
  - 멀티테넌시: 서브도메인 기반 호스트 분리
  - 인증: Firebase + Sanctum SPA 세션
  - 관리자: Filament (매장) + Nova (마스터)
  - 고객앱: React 19.1 PWA
  - 결제: operacionesenlinea.com (멕시코)
  - 알림: WhatsApp Business API

## 금지 사항
- 민감 정보 하드코딩, 강제 푸시, 보호 규칙 우회
- 무분별한 대용량 변경(>300줄) PR 1건에 몰아넣기

## 추가 레퍼런스
- 내부 가이드
  - 로컬 가이드: `CLAUDE.local.md`
  - 저장소 규칙: `docs/repo/rules.md`
  - 화이트페이퍼: `docs/whitepaper.md`
  - 프로젝트 1: `docs/milestones/project-1.md`
  - 인증/세션: `docs/auth.md`
  - 환경/도메인: `docs/devops/environments.md`
- 외부 문서(버전 기준)
  - Laravel 12: https://laravel.com/docs/12.x
  - Filament 4: https://filamentphp.com/docs
  - Nova v5: https://nova.laravel.com/docs/5.0/
  - React 19: https://react.dev/
  - TailwindCSS: https://tailwindcss.com/docs
  - daisyUI: https://daisyui.com/components/

## 다국어 지원
- 다국어 파일은 'lang' 폴더에 위치
- 한국어, 영어, 스페인어(멕시코) 지원

---

# CLAUDE 실행 예시 프롬프트(샘플)

```
역할: 너는 이 저장소의 CLAUDE 코드 어시스턴트다. 모든 사고/응답은 한국어로 하고, 문서 우선 원칙을 지킨다.
목표: docs/milestones/project-1.md에 정의된 범위 내에서 인증/세션 문서 보강 후, 필요한 경우 최소한의 코드 스켈레톤을 PR로 제안하라.
제약: main/prod에 직접 푸시 금지, PR 경유. 변경 전후 링크를 명확히 작성.
출력: 변경 이유, 영향도, 파일 경로, 코드 블록(언어 표기), 후속 TODO.
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.13
- filament/filament (FILAMENT) - v4
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/nova (NOVA) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/scout (SCOUT) - v10
- livewire/livewire (LIVEWIRE) - v3
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/dusk (DUSK) - v8
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2
- @inertiajs/react (INERTIA) - v2
- eslint (ESLINT) - v9
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4


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

### Important Version 4 Changes
- File visibility is now `private` by default.
- The `deferFilters` method from Filament v3 is now the default behavior in Filament v4, so users must click a button before the filters are applied to the table. To disable this behavior, you can use the `deferFilters(false)` method.
- The `Grid`, `Section`, and `Fieldset` layout components no longer span all columns by default.
- The `all` pagination page method is not available for tables by default.
- All action classes extend `Filament\Actions\Action`. No action classes exist in `Filament\Tables\Actions`.
- The `Form` & `Infolist` layout components have been moved to `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.
- A new `Repeater` component for Forms has been added.
- Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other options are available and documented.

### Organize Component Classes Structure
- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


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
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
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


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>

=== Design Guidelines ===

📱 Layout & Responsiveness
- Use viewport-based units (vw, vh, vmin, vmax) for widths and heights instead of px
- Use rem for typography (1rem = 16px base)
- Use calc(1rem + 1vw) for responsive font sizes
- Use padding: min(5vw, 2rem) for adaptive spacing
- Ensure mobile-first design that adapts to various phone screen sizes
- Always support light mode & dark mode switch

🎨 Brand & Colors (Light/Dark Mode)
Primary: #03D67B  
Primary Variant: #00B96F  
Secondary: #7A4FFC  
Secondary Variant: #522CC6  

Neutrals Light: #434343  
Neutrals Dark: #F6F6F6  
Neutrals Secondary: #C4C4C4  

Background Light: #F6F6F6  
Background Dark: #434343  
Background Variant Light: #FFFFFF  
Background Variant Dark: #202020  

State Info: #C5F447  
State Success: #81B7FF  
State Warning: #FFD6A8  
State Error: #FF8955  
Unavailable: #F1EEEE  
Line Primary: #202020  
Line Secondary: #878787  
Line Subline: #A0A5A3  
Line Icon: #00B96F  
Line Error: #FF8955  

🖋 Typography
- Use Noto Sans for all text
- FontPrimary: #202020
- FontSecondary: #878787
- FontSecondary Variant: #A0A5A3
- FontNeutrals: #F6F6F6
- FontError: #FF8955
- Use rem for consistency and vw for responsiveness

🖼 Images & Icons
- Always support png, svg imports directly
- Keep vector quality for scaling
- Reference images should be inserted as-is (not converted)

🌍 Multi-language
- Base language: Korean,Spanish,English
- Support automatic Korean ↔ Spanish switching
- All text must be editable for translation

💱 Currency & Units
- Always show values with dynamic currency unit (KRW, MXN, USD, EUR)
- Implement exchange-rate-ready text fields (placeholders: {{price}} {{currency}})

🛠 Features to include
- Table Order page (QR code order flow)
- Pickup page (store selection, waiting time, order progress)
- Responsive navigation (tab bar or drawer depending on screen size)
- Support for order details, payment, receipt view
- Buttons and inputs styled with brand colors, rounded corners (2xl), soft shadows
