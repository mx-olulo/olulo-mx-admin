<?php

declare(strict_types=1);

// @CODE:I18N-001 | SPEC: SPEC-I18N-001.md | TEST: tests/Feature/Filament/OrganizationResourceI18nTest.php

namespace App\Filament\Organization\Resources\Organizations\Tables;

use App\Filament\Organization\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.organizations.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_email')
                    ->label(__('filament.organizations.columns.contact_email'))
                    ->searchable(),
                TextColumn::make('contact_phone')
                    ->label(__('filament.organizations.columns.contact_phone'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('filament.organizations.columns.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('filament.organizations.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament.organizations.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('filament.common.actions.view')),
                EditAction::make()
                    ->label(__('filament.common.actions.edit')),
                Action::make('activities')
                    ->label(__('filament.organizations.actions.activities'))
                    ->icon('heroicon-o-clock')
                    ->url(fn (Organization $organization): string => OrganizationResource::getUrl('activities', ['record' => $organization])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
