<?php

namespace Mortezamasumi\FbUser\Resources;

use BackedEnum;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Mortezamasumi\FbUser\Resources\Pages\CreateUser;
use Mortezamasumi\FbUser\Resources\Pages\EditUser;
use Mortezamasumi\FbUser\Resources\Pages\ListUsers;
use Mortezamasumi\FbUser\Resources\Schemas\UserForm;
use Mortezamasumi\FbUser\Resources\Tables\UsersTable;
use UnitEnum;

class UserResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'export',
            'create_role_on_import',
            'force_change_password',
        ];
    }

    public static function getModelLabel(): string
    {
        return __(config('fb-user.navigation.model_label'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(config('fb-user.navigation.plural_model_label'));
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(config('fb-user.navigation.group'));
    }

    public static function getNavigationParentItem(): ?string
    {
        return __(config('fb-user.navigation.parent_item'));
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('fb-user.navigation.icon');
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('fb-user.navigation.active_icon') ?? static::getNavigationIcon();
    }

    public static function getNavigationBadge(): ?string
    {
        return config('fb-user.navigation.badge')
            ? Number::format(
                number: static::getModel()::when(
                    ! Auth::user()->hasRole('super_admin'),
                    fn (Builder $query) => $query->role(roles: ['super_admin'], without: true)
                )
                    ->where('active', true)
                    ->count(),
                locale: App::getLocale()
            )
            : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return config('fb-user.navigation.badge_tooltip');
    }

    public static function getNavigationSort(): ?int
    {
        return config('fb-user.navigation.sort');
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->reverseName;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'first_name', 'last_name', 'nid', 'profile'];
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
