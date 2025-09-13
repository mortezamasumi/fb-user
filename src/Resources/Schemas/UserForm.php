<?php

namespace Mortezamasumi\FbUser\Resources\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbProfile\Component\Profile;
use Mortezamasumi\FbUser\Resources\UserResource;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(4)->schema(Profile::components()),
                Flex::make([
                    Grid::make(2)->schema(static::passwordSection()),
                    Grid::make(2)->schema(static::accountInfo()),
                    Grid::make(1)
                        ->schema([
                            Checkbox::make('active')
                                ->label(__('fb-user::fb-user.form.active'))
                                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
                            Checkbox::make('force_change_password')
                                ->label(__('fb-user::fb-user.form.force_change_password'))
                                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin') && ! Auth::user()->can('ForceChangePassword:User')),
                        ])
                        ->grow(false)
                ])
                    ->from('md')
                    ->columns(5),
                Grid::make(1)->schema(UserResource::getModel()::extraFormSection()),
            ])
            ->columns(1);
    }

    public static function accountInfo(): array
    {
        return [
            Select::make('roles')
                ->label(__('fb-user::fb-user.form.roles'))
                ->multiple()
                ->preload()
                ->required()
                ->live(debounce: 750, condition: true)
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->unless(
                            Auth::user()->hasRole('super_admin'),
                            fn (Builder $query) => $query->where('name', '<>', 'super_admin')
                        )
                )
                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
            DateTimePicker::make('expiration_date')
                ->label(__('fb-user::fb-user.form.expiration_date'))
                ->jDateTime()
                ->seconds(false)
                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
        ];
    }

    public static function passwordSection(): array
    {
        return [
            TextInput::make('password')
                ->label(__('filament-panels::auth/pages/register.form.password.label'))
                ->required(fn (string $operation) => $operation === 'create')
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->dehydrated(static fn (?string $state): bool => filled($state))
                ->afterStateHydrated(fn (TextInput $component) => $component->state(''))
                ->same('password_confirmation')
                ->maxLength(255)
                ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute')),
            TextInput::make('password_confirmation')
                ->label(__('filament-panels::auth/pages/register.form.password_confirmation.label'))
                ->requiredWith('password')
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->maxLength(255)
                ->dehydrated(false),
        ];
    }
}
