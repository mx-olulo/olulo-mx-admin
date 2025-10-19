<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Stores RelationManager - Brand의 소속 Store 관리
 */

namespace App\Filament\Organization\Resources\Brands\RelationManagers;

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

class StoresRelationManager extends RelationManager
{
    protected static string $relationship = 'stores';

    public static function getTitle(?\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament.brands.relations.stores');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.stores.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament.stores.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('address')
                    ->label(__('filament.stores.fields.address'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label(__('filament.stores.fields.phone'))
                    ->tel()
                    ->maxLength(255),
                Select::make('relationship_type')
                    ->label(__('filament.stores.fields.relationship_type'))
                    ->options([
                        RelationshipType::OWNED->value => RelationshipType::OWNED->label(),
                        RelationshipType::TENANT->value => RelationshipType::TENANT->label(),
                    ])
                    ->default(RelationshipType::OWNED->value)
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('filament.stores.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.stores.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label(__('filament.stores.columns.address'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('relationship_type')
                    ->label(__('filament.stores.columns.relationship_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                IconColumn::make('is_active')
                    ->label(__('filament.stores.columns.is_active'))
                    ->boolean(),
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
