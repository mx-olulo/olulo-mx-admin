<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Brand Resource - Organization Panel
 *
 * - 3-Layer 권한 체계 (Spatie + Filament + Policy)
 * - RelationshipType 기반 삭제 제어
 * - Activity Log 통합
 */

namespace App\Filament\Organization\Resources\Brands;

use App\Filament\Organization\Resources\Brands\Pages\CreateBrand;
use App\Filament\Organization\Resources\Brands\Pages\EditBrand;
use App\Filament\Organization\Resources\Brands\Pages\ListBrands;
use App\Filament\Organization\Resources\Brands\Pages\ViewBrand;
use App\Filament\Organization\Resources\Brands\RelationManagers\StoresRelationManager;
use App\Filament\Organization\Resources\Brands\Schemas\BrandForm;
use App\Filament\Organization\Resources\Brands\Schemas\BrandInfolist;
use App\Filament\Organization\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    public static function getNavigationLabel(): string
    {
        return __('filament.brands.resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament.brands.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.brands.resource.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BrandInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'view' => ViewBrand::route('/{record}'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }

    /**
     * Brand 목록 조회 권한 확인
     *
     * Policy의 viewAny() 메서드로 위임
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('viewAny', Brand::class);
    }

    /**
     * Brand 생성 권한 확인
     *
     * Policy의 create() 메서드로 위임
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('create', Brand::class);
    }

    /**
     * 특정 Brand 조회 권한 확인
     *
     * Policy의 view() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('view', $record);
    }

    /**
     * 특정 Brand 수정 권한 확인
     *
     * Policy의 update() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('update', $record);
    }

    /**
     * 특정 Brand 삭제 권한 확인
     *
     * Policy의 delete() 메서드로 위임
     * - tenant 관계: 삭제 불가
     * - 활성 Store 보유: 삭제 불가
     */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('delete', $record);
    }

    /**
     * 특정 Brand 복원 권한 확인
     *
     * Policy의 restore() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canRestore(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('restore', $record);
    }

    /**
     * 특정 Brand 영구 삭제 권한 확인
     *
     * Policy의 forceDelete() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canForceDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('forceDelete', $record);
    }
}
