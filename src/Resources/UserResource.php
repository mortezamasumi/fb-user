<?php

namespace Mortezamasumi\FbUser\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbPersian\Facades\FbPersian;
use Mortezamasumi\FbUser\Resources\Pages\CreateUser;
use Mortezamasumi\FbUser\Resources\Pages\EditUser;
use Mortezamasumi\FbUser\Resources\Pages\ListUsers;
use Mortezamasumi\FbUser\Resources\Schemas\UserForm;
use Mortezamasumi\FbUser\Resources\Tables\UsersTable;

class UserResource extends Resource
{
    public static function getNavigationIcon(): string
    {
        return config('fb-user.navigation.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return config('fb-user.navigation.sort');
    }

    public static function getNavigationLabel(): string
    {
        return __(config('fb-user.navigation.label'));
    }

    public static function getNavigationGroup(): ?string
    {
        return __(config('fb-user.navigation.group'));
    }

    public static function getModelLabel(): string
    {
        return __(config('fb-user.navigation.model_label'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(config('fb-user.navigation.plural_model_label'));
    }

    public static function getNavigationParentItem(): ?string
    {
        return config('fb-user.navigation.parent_item');
    }

    public static function getActiveNavigationIcon(): string|Htmlable|null
    {
        return config('fb-user.navigation.active_icon') ?? static::getNavigationIcon();
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->getReverseName();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'first_name', 'last_name', 'nid', 'profile'];
    }

    public static function getNavigationBadge(): ?string
    {
        return config('fb-user.navigation.show_count')
            ? FbPersian::digit(
                static::getModel()::when(
                    ! Auth::user()->hasRole('super_admin'),
                    fn (Builder $query) => $query->role(roles: ['super_admin'], without: true)
                )
                    ->where('active', true)
                    ->count(),
            )
            : null;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->when(
                ! Auth::user()->hasRole('super_admin'),
                fn (Builder $query) => $query->role(roles: ['super_admin'], without: true)
            );
    }
}
