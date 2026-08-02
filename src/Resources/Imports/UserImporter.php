<?php

namespace Mortezamasumi\FbUser\Resources\Imports;

use Ariaieboy\Jalali\CalendarUtils;
use Exception;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Forms\Components\Checkbox;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbEssentials\Traits\ImportCompletedNotificationBody;
use Mortezamasumi\FbProfile\Enums\GenderEnum;
use Mortezamasumi\FbUser\Models\User;
use Spatie\Permission\Models\Role;

class UserImporter extends Importer
{
    use ImportCompletedNotificationBody;

    public static function getDate(mixed $state): ?Carbon
    {
        try {
            throw_unless($state);
            throw_unless(preg_match('/[- \/]/', $state));

            $date = Carbon::parse($state);

            if ($date->year < 1500) {
                $dateTime = CalendarUtils::toGregorianDate($date->year, $date->month, $date->day);

                $date = Carbon::createFromTimestamp($dateTime->getTimestamp(), $dateTime->getTimezone());

                $date->setTimeFrom($date);
            }
        } catch (Exception $e) {
            $date = null;
        }

        return $date;
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')
                ->label(__('fb-user::fb-user.importer.first_name'))
                ->guess(['First name', 'first name', 'fname', 'نام'])
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('last_name')
                ->label(__('fb-user::fb-user.importer.last_name'))
                ->guess(['Last name', 'last name', 'lname', 'نام خانوادگی'])
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('nid')
                ->label(__('fb-user::fb-user.importer.nid'))
                ->guess(['NID', 'National Id', 'NationalId', 'کدملی', 'شماره ملی', 'شماره‌ملی'])
                ->requiredMapping(config('fb-profile.nid_required'))
                ->rules(
                    fn () => config('fb-profile.nid_required')
                        ? ['required', 'max:255']
                        : ['max:255']
                ),
            ImportColumn::make('gender')
                ->label(__('fb-user::fb-user.importer.gender'))
                ->fillRecordUsing(function (Model $record, ?string $state): void {
                    /** @var User $record */
                    $record->gender = GenderEnum::tryFrom((string) $state) ?? GenderEnum::Undefined;
                }),
            ImportColumn::make('birth_date')
                ->label(__('fb-user::fb-user.importer.birth_date'))
                ->fillRecordUsing(function (Model $record, ?string $state): void {
                    /** @var User $record */
                    $record->birth_date = static::getDate($state);
                }),
            ImportColumn::make('username')
                ->label(__('fb-user::fb-user.importer.username'))
                ->requiredMapping(config('fb-profile.username_required'))
                ->guess(['نام کاربری', 'نام‌کاربری'])
                ->rules(
                    fn () => config('fb-profile.username_required')
                        ? ['required', 'max:255', 'unique:users,username']
                        : ['nullable', 'max:255', 'unique:users,username']
                ),
            ImportColumn::make('password')
                ->label(__('fb-user::fb-user.importer.password'))
                ->guess(['پسورد', 'رمزعبور']),
            ImportColumn::make('email')
                ->label(__('fb-user::fb-user.importer.email'))
                ->requiredMapping(config('fb-profile.email_required'))
                ->guess(['e-mail', 'ایمیل', 'پست الکترونیک'])
                ->rules(
                    fn () => config('fb-profile.email_required')
                        ? ['required', 'max:255', 'unique:users,email']
                        : ['nullable', 'max:255', 'unique:users,email']
                ),
            ImportColumn::make('mobile')
                ->label(__('fb-user::fb-user.importer.mobile'))
                ->requiredMapping(config('fb-profile.mobile_required'))
                ->guess(['موبایل', 'شماره همراه', 'شماره‌همراه', 'تلفن همراه', 'تلفن‌همراه'])
                ->rules(
                    fn () => config('fb-profile.mobile_required')
                        ? ['required', 'max:255', 'unique:users,mobile']
                        : ['nullable', 'max:255', 'unique:users,mobile']
                ),
            ImportColumn::make('active')
                ->label(__('fb-user::fb-user.importer.active')),
            ImportColumn::make('expiration_date')
                ->label(__('fb-user::fb-user.importer.expiration_date'))
                ->fillRecordUsing(function (Model $record, ?string $state): void {
                    /** @var User $record */
                    $record->expiration_date = static::getDate($state);
                }),
            ImportColumn::make('roles')
                ->label(__('fb-user::fb-user.importer.roles'))
                ->requiredMapping()
                ->guess(['Roles', 'نقشها', 'نقش ها', 'نقش‌ها'])
                ->array(',')
                ->rules(['required', 'array', 'min:1'])
                ->nestedRecursiveRules(fn () => Auth::user()?->can('Create:Role') ? [] : ['exists:roles,name'])
                ->fillRecordUsing(fn () => null),
        ];
    }

    public function resolveRecord(): ?Model
    {
        $provider = Auth::getProvider();

        if (! $provider instanceof EloquentUserProvider) {
            return null;
        }

        /** @var class-string<User> $model */
        $model = $provider->getModel();

        return $model::firstOrNew(config('fb-auth.auth_type')->resolveRecord($this->data));
    }

    protected function afterFill(): void
    {
        /** @var User $record */
        $record = $this->getRecord();
        $record->password = $this->data['password'] ?? $record->username;
        $record->active = (bool) $record->active;
    }

    protected function afterSave(): void
    {
        $roles = array_filter($this->data['roles'], fn ($item) => $item !== 'super_admin');

        if ($this->options['createMissedRoles'] ?? false) {
            array_walk($roles, function ($role) {
                Role::firstOrCreate(
                    ['name' => $role],
                    ['guard_name' => 'web']
                );
            });
        }

        Role::whereIn('name', $roles)
            ->each(function ($role) {
                /** @var User $record */
                $record = $this->getRecord();
                $role->users()->attach([$record->id]);
            });
    }

    /**
     * @return array<int, mixed>
     */
    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('createMissedRoles')
                ->label(__('fb-user::fb-user.importer.create_role_if_not_exists'))
                ->visible((bool) Auth::user()?->can('CreateRoleOnImport:User')),
        ];
    }
}
