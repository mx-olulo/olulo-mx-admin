<?php

declare(strict_types=1);

namespace App\Filament\Store\Pages;

use App\Models\User;
use App\Services\OnboardingService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Wizard;
use Illuminate\Support\Facades\Auth;

class OnboardingWizard extends Page implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected string $view = 'filament.store.pages.onboarding-wizard';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        // 이미 소속이 있는 사용자는 대시보드로 리디렉션
        $user = Auth::user();
        $panel = Filament::getCurrentPanel();

        if ($user instanceof User && $panel instanceof Panel && $user->getTenants($panel)->isNotEmpty()) {
            $this->redirect(route('filament.store.pages.dashboard'));
        }

        $this->fillForm();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema($this->getFormSchema())
                    ->statePath('data')
            ),
        ];
    }

    /**
     * @return array<\Filament\Schemas\Components\Component>
     */
    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                Wizard\Step::make('유형 선택')
                    ->description('조직 또는 매장 중 하나를 선택하세요')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Select::make('entity_type')
                            ->label('생성할 유형')
                            ->options([
                                'organization' => '조직',
                                'store' => '매장',
                            ])
                            ->required()
                            ->helperText('조직은 여러 매장을 관리할 수 있습니다. 매장은 독립적으로 운영됩니다.')
                            ->live(),
                    ]),

                Wizard\Step::make('기본 정보')
                    ->description('필수 정보를 입력하세요')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        TextInput::make('name')
                            ->label('이름')
                            ->required()
                            ->maxLength(255)
                            ->helperText('조직 또는 매장의 공식 명칭을 입력하세요')
                            ->unique(
                                /** @param Get $get */
                                table: fn ($get): string => $get('entity_type') === 'organization' ? 'organizations' : 'stores',
                                column: 'name'
                            ),
                    ]),
            ])
                ->submitAction(view('filament.components.wizard-submit')),
        ];
    }

    /**
     * @throws \Exception
     */
    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new \Exception('User must be authenticated');
        }

        $onboardingService = app(OnboardingService::class);

        if ($data['entity_type'] === 'organization') {
            $onboardingService->createOrganization($user, ['name' => $data['name']]);
        } else {
            $onboardingService->createStore($user, ['name' => $data['name']]);
        }

        $this->redirect(route('filament.store.pages.dashboard'));
    }
}
