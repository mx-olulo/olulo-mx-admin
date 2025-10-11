<?php

declare(strict_types=1);

namespace App\Filament\Organization\Resources\Organizations\Pages;

use App\Enums\ScopeType;
use App\Filament\Organization\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ListOrganizationActivities extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = OrganizationResource::class;

    public ?Organization $record = null;

    public function getView(): string
    {
        return 'filament.pages.list-activities';
    }

    public function mount(int|string $record): void
    {
        $this->record = static::getResource()::resolveRecordRouteBinding($record);

        if (! $this->record instanceof \App\Models\Organization) {
            abort(404);
        }

        // 권한 검증: view-activities 권한 체크
        if (! auth()->user()?->can('view-activities')) {
            abort(403, 'You do not have permission to view activity logs.');
        }

        // 멀티테넌시 격리: 현재 테넌트(Role)가 이 Organization에 접근 가능한지 확인
        $tenant = Filament::getTenant();
        if ($tenant instanceof \App\Models\Role) {
            // PLATFORM/SYSTEM 스코프는 모든 Organization 접근 가능 (Gate::before에서 처리됨)
            // ORGANIZATION 스코프는 자신의 Organization만 접근 가능
            if ($tenant->scope_type === ScopeType::ORGANIZATION->value) {
                if ($tenant->scope_ref_id !== $this->record->id) {
                    abort(403, 'You can only access activity logs for your own organization.');
                }
            }
        }
    }

    public function table(Table $table): Table
    {
        if (! $this->record instanceof \App\Models\Organization) {
            abort(404);
        }

        return $table
            ->query(
                Activity::query()
                    ->with('causer') // N+1 쿼리 방지: causer eager loading
                    ->where('subject_type', $this->record::class)
                    ->where('subject_id', $this->record->id)
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->searchable(),

                Tables\Columns\TextColumn::make('properties')
                    ->label('Changes')
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) {
                            return '-';
                        }

                        $changes = [];

                        if (isset($state['attributes'])) {
                            foreach ($state['attributes'] as $key => $value) {
                                $old = $state['old'][$key] ?? null;
                                if ($old !== $value) {
                                    $changes[] = "{$key}: " . ($old ?? 'null') . " → {$value}";
                                }
                            }
                        }

                        return $changes !== [] ? implode(', ', $changes) : 'Created';
                    })
                    ->wrap()
                    ->limit(100),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('description')
                    ->label('Event Type')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function getTitle(): string
    {
        return 'Activity Log: ' . ($this->record->name ?? 'Organization');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Organization')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record]))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
