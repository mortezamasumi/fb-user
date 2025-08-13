<?php

namespace Mortezamasumi\FbUser\Exports;

use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class UserExporter extends Exporter
{
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')
                ->label(__('fb-user::fb-user.exporter.first_name')),
            ExportColumn::make('last_name')
                ->label(__('fb-user::fb-user.exporter.last_name')),
            ExportColumn::make('nid')
                ->label(__('fb-user::fb-user.exporter.nid')),
            ExportColumn::make('gender')
                ->label(__('fb-user::fb-user.exporter.gender'))
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
            ExportColumn::make('birth_date')
                ->label(__('fb-user::fb-user.exporter.birth_date'))
                ->jDate(),
            ExportColumn::make('username')
                ->label(__('fb-user::fb-user.exporter.username')),
            ExportColumn::make('email')
                ->label(__('fb-user::fb-user.exporter.email')),
            ExportColumn::make('mobile')
                ->label(__('fb-user::fb-user.exporter.mobile')),
            ExportColumn::make('active')
                ->label(__('fb-user::fb-user.exporter.active')),
            ExportColumn::make('account_expires_at')
                ->label(__('fb-user::fb-user.exporter.expiration_date'))
                ->jDate(),
            ExportColumn::make('roles.name')
                ->label(__('fb-user::fb-user.exporter.roles')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        if (App::getLocale() === 'fa') {
            $body = 'برون برد انجام شد و '.Number::format(number: number_format($export->successful_rows), locale: App::getLocale()).' سطر ایجاد شد';

            if ($failedRowsCount = $export->getFailedRowsCount()) {
                $body .= 'و تعداد '
                    .Number::format(number: number_format($failedRowsCount), locale: App::getLocale())
                    .' سطر دارای خطا بود و ایجاد نشد';
            }
        } else {
            $body = 'Export has completed and '.number_format($export->successful_rows).' '.Str::plural('row', $export->successful_rows).' exported.';

            if ($failedRowsCount = $export->getFailedRowsCount()) {
                $body .= ', '.number_format($failedRowsCount).' '.Str::plural('row', $failedRowsCount).' failed to export.';
            }
        }

        return $body;
    }
}
