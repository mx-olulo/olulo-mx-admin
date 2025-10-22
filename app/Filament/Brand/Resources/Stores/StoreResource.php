<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Store Resource - Brand Panel
 *
 * - 3-Layer 권한 체계 (Spatie + Filament + Policy)
 * - RelationshipType 기반 삭제 제어
 * - Activity Log 통합
 */

namespace App\Filament\Brand\Resources\Stores;

use App\Filament\Brand\Resources\Stores\Pages\CreateStore;
use App\Filament\Brand\Resources\Stores\Pages\EditStore;
use App\Filament\Brand\Resources\Stores\Pages\ListStores;
use App\Filament\Brand\Resources\Stores\Pages\ViewStore;
use App\Filament\Brand\Resources\Stores\Schemas\StoreForm;
use App\Filament\Brand\Resources\Stores\Schemas\StoreInfolist;
use App\Filament\Brand\Resources\Stores\Tables\StoresTable;
use App\Models\Store;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationLabel(): string
    {
        return __('filament.stores.resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament.stores.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.stores.resource.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return StoreForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StoreInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoresTable::configure($table);
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
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'view' => ViewStore::route('/{record}'),
            'edit' => EditStore::route('/{record}/edit'),
        ];
    }

    /**
     * Store 목록 조회 권한 확인
     *
     * Policy의 viewAny() 메서드로 위임
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('viewAny', Store::class);
    }

    /**
     * Store 생성 권한 확인
     *
     * Policy의 create() 메서드로 위임
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('create', Store::class);
    }

    /**
     * 특정 Store 조회 권한 확인
     *
     * Policy의 view() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('view', $record);
    }

    /**
     * 특정 Store 수정 권한 확인
     *
     * Policy의 update() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('update', $record);
    }

    /**
     * 특정 Store 삭제 권한 확인
     *
     * Policy의 delete() 메서드로 위임
     * - tenant 관계: 삭제 불가
     */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('delete', $record);
    }

    /**
     * 특정 Store 복원 권한 확인
     *
     * Policy의 restore() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canRestore(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('restore', $record);
    }

    /**
     * 특정 Store 영구 삭제 권한 확인
     *
     * Policy의 forceDelete() 메서드로 위임 (권한 + 소유권 체크)
     */
    public static function canForceDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('forceDelete', $record);
    }
}
