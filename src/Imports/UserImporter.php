<?php

namespace Mortezamasumi\FbUser\Imports;

use Ariaieboy\Jalali\CalendarUtils;
use Carbon\Carbon;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Forms\Components\Checkbox;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mortezamasumi\Fbase\Enums\GenderEnum;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\Persian\Facades\Persian;
use Spatie\Permission\Models\Role;
use Exception;

class UserImporter extends Importer
{
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
                    try {
                        $record->gender = GenderEnum::tryFrom($state);
                    } catch (Exception $e) {
                        $record->gender = GenderEnum::Undefined;
                    }
                }),
            ImportColumn::make('birth_date')
                ->label(__('fb-user::fb-user.importer.birth_date'))
                ->fillRecordUsing(function (Model $record, ?string $state): void {
                    try {
                        throw_unless($state, 'is empty');

                        $date = Carbon::parse($state);

                        if ($date->year < 1500) {
                            $dateTime = CalendarUtils::toGregorianDate($date->year, $date->month, $date->day);

                            $record->birth_date = Carbon::createFromTimestamp($dateTime->getTimestamp(), $dateTime->getTimezone());

                            $record->birth_date->setTimeFrom($date);
                        } else {
                            $record->birth_date = $date;
                        }
                    } catch (Exception $e) {
                        $record->birth_date = null;
                    }
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
            ImportColumn::make('expired_at')
                ->label(__('fb-user::fb-user.importer.expired_at'))
                ->fillRecordUsing(function (Model $record, ?string $state): void {
                    try {
                        throw_unless($state, 'is empty');

                        $date = Carbon::parse($state);

                        if ($date->year < 1500) {
                            $dateTime = CalendarUtils::toGregorianDate($date->year, $date->month, $date->day);

                            $record->expired_at = Carbon::createFromTimestamp($dateTime->getTimestamp(), $dateTime->getTimezone());

                            $record->expired_at->setTimeFrom($date);
                        } else {
                            $record->expired_at = $date;
                        }
                    } catch (Exception $e) {
                        $record->expired_at = null;
                    }
                }),
            ImportColumn::make('roles')
                ->label(__('fb-user::fb-user.importer.roles'))
                ->requiredMapping()
                ->guess(['Roles', 'نقشها', 'نقش ها', 'نقش‌ها'])
                ->array(',')
                ->rules(['required', 'array', 'min:1'])
                ->nestedRecursiveRules(fn () => Auth::user()->can('create_role') ? [] : ['exists:roles,name'])
                ->fillRecordUsing(fn () => null),
        ];
    }

    public function resolveRecord(): ?Model
    {
        /** @disregard */
        return Auth::getProvider()->getModel()::firstOrNew(match (config('fb-auth.auth_type')) {
            AuthType::User => ['username' => $this->data['username']],
            AuthType::Mobile => ['mobile' => $this->data['mobile']],
            AuthType::Code => ['email' => $this->data['email']],
            AuthType::Link => ['email' => $this->data['email']],
        });
    }

    // protected function afterFill(): void
    // {
    //     $this->getRecord()->password = $this->data['password'] ?? $this->getRecord()->username;
    // }

    // protected function afterSave(): void
    // {
    //     $roles = array_filter($this->data['roles'], fn ($item) => $item !== 'super_admin');

    //     if ($this->options['createMissedRoles'] ?? false) {
    //         array_walk($roles, function ($role) {
    //             Role::firstOrCreate(
    //                 ['name' => $role],
    //                 ['guard_name' => 'web']
    //             );
    //         });
    //     }

    // Role::whereIn('name', $roles)
    //     ->each(fn ($role) => $role->users()->attach([$this->getRecord()->id]));
    // }

    public static function getCompletedNotificationBody(Import $import): string
    {
        if (app()->getLocale() === 'fa') {
            $postfix = $import->successful_rows > 1 ? '' : '';

            $body = 'بارگذاری انجام شد و '.Persian::enTOfa(number_format($import->successful_rows)).' سطر ایجاد شد'.$postfix;

            if ($failedRowsCount = $import->getFailedRowsCount()) {
                $postfix = $failedRowsCount > 1 ? '' : '';

                $body .= 'و تعداد '.Persian::enTOfa(number_format($failedRowsCount)).' سطر دارای خطا بود'.$postfix.' و بارگذاری نشد'.$postfix;
            }
        } else {
            $body = 'Your users import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

            if ($failedRowsCount = $import->getFailedRowsCount()) {
                $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
            }
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('createMissedRoles')
                ->label(__('fb-user::fb-user.importer.create_role_if_not_exists'))
                ->visible(Auth::user()->can('create_role_on_import_user'))
        ];
    }
}
