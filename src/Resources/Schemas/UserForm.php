<?php

namespace Mortezamasumi\FbUser\Resources\Schemas;

use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
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
                Section::make(__('fb-user::fb-user.form.user_profile'))
                    ->schema(Profile::components())
                    ->columns(3),
                Flex::make([
                    Section::make(__('fb-user::fb-user.form.account'))
                        ->schema(static::accountInfo())
                        ->columns(3),
                    Section::make(__('fb-user::fb-user.form.password'))
                        ->schema(static::passwordSection(true))
                        ->grow(false)
                        ->columns(1),
                ])
                    ->columnSpanFull()
                    ->from('md'),
                ...UserResource::getModel()::extraFormSection(),
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
                ->columnSpan(2)
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
                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
            Toggle::make('active')
                ->label(__('fb-user::fb-user.form.active'))
                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
            Toggle::make('force_change_password')
                ->label(__('fb-user::fb-user.form.force_change_password'))
                ->disabled(fn (?Model $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin'))
                ->columnSpan(2),
        ];
    }

    public static function passwordSection(bool $showForce = false): array
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
            Checkbox::make('force_change_password')
                ->label(__('fb-user::fb-user.form.force_change_password'))
                ->visible(Auth::user()->can('force_change_password_user') && $showForce),
        ];
    }
}
