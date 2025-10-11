<?php

declare(strict_types=1);

namespace App\Filament\Organization\Resources\Organizations;

use App\Filament\Organization\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Organization\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Organization\Resources\Organizations\Pages\ListOrganizationActivities;
use App\Filament\Organization\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Organization\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Organization\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Organization\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Organization\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'view' => ViewOrganization::route('/{record}'),
            'edit' => EditOrganization::route('/{record}/edit'),
            'activities' => ListOrganizationActivities::route('/{record}/activities'),
        ];
    }

    /**
     * Organization 목록 조회 권한 확인
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view-organizations') ?? false;
    }

    /**
     * Organization 생성 권한 확인
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->can('create-organizations') ?? false;
    }

    /**
     * 특정 Organization 조회 권한 확인
     */
    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view-organizations') ?? false;
    }

    /**
     * 특정 Organization 수정 권한 확인
     */
    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update-organizations') ?? false;
    }

    /**
     * 특정 Organization 삭제 권한 확인
     */
    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete-organizations') ?? false;
    }

    /**
     * 특정 Organization 복원 권한 확인
     */
    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('restore-organizations') ?? false;
    }

    /**
     * 특정 Organization 영구 삭제 권한 확인
     */
    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->can('force-delete-organizations') ?? false;
    }
}
