<?php

declare(strict_types=1);

namespace App\Filament\Organization\Resources\Organizations\Pages;

use App\Filament\Organization\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
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
    }

    public function table(Table $table): Table
    {
        if (! $this->record instanceof \App\Models\Organization) {
            abort(404);
        }

        return $table
            ->query(
                Activity::query()
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
