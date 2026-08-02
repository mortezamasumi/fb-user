<?php

namespace Mortezamasumi\FbUser\Resources\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbProfile\Schemas\ProfileForm;
use Mortezamasumi\FbUser\Models\User;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $provider = Auth::getProvider();

        if (! $provider instanceof EloquentUserProvider) {
            return $schema->columns(1);
        }

        /** @var class-string<User> $userClass */
        $userClass = $provider->getModel();
        /** @var User|null $user */
        $user = Auth::user();

        if (method_exists($userClass, 'customUserForm')) {
            return $schema
                ->components($userClass::customUserForm())
                ->columns(1);
        }

        return $schema
            ->components([
                Grid::make(4)->schema(ProfileForm::components()),
                Flex::make([
                    Grid::make(2)->schema(static::passwordComponents()),
                    Grid::make(2)->schema(static::accountComponents()),
                    Grid::make(1)
                        ->schema([
                            Checkbox::make('active')
                                ->label(__('fb-user::fb-user.form.active'))
                                ->disabled(fn (?User $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
                            Checkbox::make('force_change_password')
                                ->label(__('fb-user::fb-user.form.force_change_password'))
                                ->disabled(fn (?User $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin') && ! $user?->can('ForceChangePassword:User')),
                        ])
                        ->grow(false),
                ])
                    ->from('md')
                    ->columns(5),
                Grid::make(1)->schema($userClass::extraFormSection()),
            ])
            ->columns(1);
    }

    /**
     * @return array<int, mixed>
     */
    public static function accountComponents(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

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
                            $user?->hasRole('super_admin'),
                            fn (Builder $query) => $query->where('name', '<>', 'super_admin')
                        )
                )
                ->disabled(fn (?User $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
            DateTimePicker::make('expiration_date')
                ->label(__('fb-user::fb-user.form.expiration_date'))
                ->jDateTime()
                ->seconds(false)
                ->disabled(fn (?User $record, $operation) => $operation === 'edit' && $record?->hasRole('super_admin')),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function passwordComponents(): array
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
