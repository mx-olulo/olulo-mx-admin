<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Brands RelationManager - Organization의 소속 Brand 관리
 */

namespace App\Filament\Organization\Resources\Organizations\RelationManagers;

use App\Enums\RelationshipType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandsRelationManager extends RelationManager
{
    protected static string $relationship = 'brands';

    public static function getTitle(?\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament.organizations.relations.brands');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.brands.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament.brands.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Select::make('relationship_type')
                    ->label(__('filament.brands.fields.relationship_type'))
                    ->options([
                        RelationshipType::OWNED->value => RelationshipType::OWNED->label(),
                        RelationshipType::TENANT->value => RelationshipType::TENANT->label(),
                    ])
                    ->default(RelationshipType::OWNED->value)
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('filament.brands.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.brands.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('relationship_type')
                    ->label(__('filament.brands.columns.relationship_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                IconColumn::make('is_active')
                    ->label(__('filament.brands.columns.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('filament.brands.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Table\Actions\CreateAction::make(),
            ])
            ->actions([
                Table\Actions\EditAction::make(),
                Table\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
