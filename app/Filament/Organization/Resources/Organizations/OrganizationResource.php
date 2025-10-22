<?php

declare(strict_types=1);

// @CODE:I18N-001 | SPEC: SPEC-I18N-001.md | TEST: tests/Feature/Filament/OrganizationResourceI18nTest.php

namespace App\Filament\Organization\Resources\Organizations;

use App\Filament\Organization\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Organization\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Organization\Resources\Organizations\Pages\ListOrganizationActivities;
use App\Filament\Organization\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Organization\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Organization\Resources\Organizations\RelationManagers\BrandsRelationManager;
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

    public static function getNavigationLabel(): string
    {
        return __('filament.organizations.resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament.organizations.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.organizations.resource.plural_label');
    }

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
            BrandsRelationManager::class,
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
     *
     * Policy의 viewAny() 메서드로 위임
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('viewAny', Organization::class);
    }

    /**
     * Organization 생성 권한 확인
     *
     * Policy의 create() 메서드로 위임
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('create', Organization::class);
    }

    /**
     * 특정 Organization 조회 권한 확인
     *
     * Policy의 view() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('view', $record);
    }

    /**
     * 특정 Organization 수정 권한 확인
     *
     * Policy의 update() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('update', $record);
    }

    /**
     * 특정 Organization 삭제 권한 확인
     *
     * Policy의 delete() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('delete', $record);
    }

    /**
     * 특정 Organization 복원 권한 확인
     *
     * Policy의 restore() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canRestore(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('restore', $record);
    }

    /**
     * 특정 Organization 영구 삭제 권한 확인
     *
     * Policy의 forceDelete() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canForceDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('forceDelete', $record);
    }
}
