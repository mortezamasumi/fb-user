<?php

namespace Mortezamasumi\FbUser\Macros;

use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Mortezamasumi\Persian\Facades\Persian;
use Closure;

/**
 * Interface declaring Table macros for IDE support
 *
 * @method static ExportColumn jDate(string|Closure|null $Tableat, ?string $timezone) jDate apply
 * @method static ExportColumn jDateTime(string|Closure|null $Tableat, ?string $timezone, bool|Closure $onlyDate) jDateTime apply
 * @method static ExportColumn localeDigit(?string $forceLocale) current locale apply
 */
interface ExportColumnMacrosInterface {}

class ExportMacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        ExportColumn::macro('jDate', function (string|Closure|null $format = null, ?string $timezone = null, ?string $forceLocale = null): ExportColumn {
            /** @var ExportColumn $this */
            $this->jDateTime($format, $timezone, $forceLocale, true);

            return $this;
        });

        ExportColumn::macro('jDateTime', function (string|Closure|null $format = null, ?string $timezone = null, ?string $forceLocale = null, bool|Closure $onlyDate = false): ExportColumn {
            /** @var ExportColumn $this */
            $this->formatStateUsing(static function (ExportColumn $column, Model $record, $state) use ($format, $timezone, $forceLocale, $onlyDate): ?string {
                if (blank($state)) {
                    return null;
                }

                $format = $column->evaluate($format, ['record' => $record, 'state' => $state]);
                $onlyDate = $column->evaluate($onlyDate, ['record' => $record, 'state' => $state]);
                $format ??= ($onlyDate ? __('persian::persian.date.format.simple') : __('persian::persian.date.format.time-simple'));

                return Persian::jDateTime($format, $state, $timezone, $forceLocale);
            });

            return $this;
        });

        ExportColumn::macro('localeDigit', function (?string $forceLocale = null): ExportColumn {
            /** @var ExportColumn $this */
            $this->formatStateUsing(static fn (mixed $state) => in_array(gettype($state), ['integer', 'double', 'string']) ? Persian::digit($state, $forceLocale) : $state);

            return $this;
        });

        ExportColumn::mixin(new class implements ExportColumnMacrosInterface {});
    }
}
