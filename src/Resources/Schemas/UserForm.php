<?php

namespace Mortezamasumi\FbUser\Resources\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Mortezamasumi\FbProfile\Enums\GenderEnum;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $model = config('auth.providers.users.model');

        if (method_exists($model, 'getUserResourceFormLayout')) {
            return $model::getUserResourceFormLayout($schema);
        }

        $additionalComponents = [];
        if (method_exists($model, 'getExtraUserResourceFormSection')) {
            $additionalComponents = $model::getExtraUserResourceFormSection($schema)->getComponents();
        }

        return $schema
            ->components([
                Section::make(__('fb-user::fb-user.form.user_profile'))
                    ->schema(static::userInfo())
                    ->columns(4),
                Flex::make([
                    Section::make(__('fb-user::fb-user.form.account'))
                        ->schema(static::accountInfo(withRole: true))
                        ->columns(3),
                    Section::make(__('fb-user::fb-user.form.password'))
                        ->schema(static::passwordSection())
                        ->grow(false)
                        ->columns(1),
                ])
                    ->columnSpanFull()
                    ->from('md'),
                Grid::make()
                    ->schema($additionalComponents),
            ])
            ->columns(1);
    }

    public static function userInfo(): array
    {
        return [
            FileUpload::make('avatar')
                ->hiddenLabel()
                ->avatar()
                ->disk('public')
                ->directory('avatars')
                ->visibility('public')
                ->maxSize(config('fbase.setup.max_avatar_size', 200))
                ->columnSpanFull()
                ->alignCenter(),
            TextInput::make('first_name')
                ->label(__('fb-user::fb-user.form.first_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label(__('fb-user::fb-user.form.last_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('nid')
                ->label(fn () => (
                    __(
                        config('fb-profile.use_passport_number_on_nid')
                            ? 'fb-user::fb-user.form.nid_pass'
                            : 'fb-user::fb-user.form.nid'
                    )
                ))
                ->required(config('fb-profile.nid_required'))
                ->regex(fn () => (
                    __(
                        config('fb-profile.use_passport_number_on_nid')
                            ? '/^(?:\d{10}|[A-Za-z].*\d{5,})$/'
                            : '/^\d{10}$/'
                    )
                ))
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->toEN(),
            TextInput::make('profile.father_name')
                ->label(__('fb-user::fb-user.form.profile.father_name'))
                ->maxLength(255),
            Select::make('gender')
                ->label(__('fb-user::fb-user.form.gender'))
                ->required(config('fb-profile.gender_required'))
                ->options(GenderEnum::class),
            DatePicker::make('birth_date')
                ->label(__('fb-user::fb-user.form.birth_date'))
                ->maxDate(now()->endOfDay())
                ->required(config('fb-profile.birth_date_required'))
                ->jDate(),
        ];
    }

    public static function accountInfo(bool $withRole = true): array
    {
        return [
            TextInput::make('mobile')
                ->label(__('fb-user::fb-user.form.mobile'))
                ->required(config('fb-profile.mobile_required'))
                ->tel()
                ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
                ->maxLength(30)
                ->toEN(),
            TextInput::make('email')
                ->label(__('filament-panels::auth/pages/edit-profile.form.email.label'))
                ->required(config('fb-profile.email_required'))
                ->rules(['email'])
                ->extraAttributes(['dir' => 'ltr'])
                ->maxLength(255)
                ->toEN(),
            TextInput::make('username')
                ->label(__('fb-user::fb-user.form.username'))
                ->required(config('fb-profile.username_required'))
                ->maxLength(255),
            Select::make('roles')
                ->label(__('fb-user::fb-user.form.roles'))
                ->multiple()
                ->preload()
                ->required()
                ->visible($withRole)
                ->live(debounce: 750, condition: true)
                ->columnSpan(2)
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->when(
                            ! Auth::user()->hasRole('super_admin'),
                            fn (Builder $query) => $query->where('name', '<>', 'super_admin')
                        )
                ),
            DateTimePicker::make('expired_at')
                ->label(__('fb-user::fb-user.form.expired_at'))
                ->jDateTime(),
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
                // ->rule(Password::defaults(), app()->isProduction())
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
                ->visible(Auth::user()->can('force_change_password_user')),
        ];
    }
}
