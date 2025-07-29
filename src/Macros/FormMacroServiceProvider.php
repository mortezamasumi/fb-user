<?php

namespace Mortezamasumi\FbUser\Macros;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Closure;

/**
 * Interface declaring Form macros for IDE support
 *
 * @method static Component jDate(string|Closure|null $format, ?string $timezone) jDate apply
 */
interface FormjDateMacrosInterface {}

/**
 * Interface declaring Form macros for IDE support
 *
 * @method static Component jDateTime(string|Closure|null $format, ?string $timezone, bool|Closure $onlyDate) jDateTime apply
 */
interface FormjDateTimeMacrosInterface {}

class FormMacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        DatePicker::macro('jDate', function (string|Closure|null $format = null, ?string $timezone = null): DatePicker {
            /** @var DatePicker $this */
            $this->jDateTime($format, $timezone, true);

            return $this;
        });

        DateTimePicker::macro('jDateTime', function (string|Closure|null $format = null, ?string $timezone = null, bool|Closure $onlyDate = false): DateTimePicker {
            /** @var DatePicker $this */
            if (App::getLocale() === 'fa') {
                $this->jalali(weekdaysShort: true)->firstDayOfWeek(6);
            } else {
                $this->native(false);
            }

            $this->displayFormat(static function (DateTimePicker $component, ?Model $record, $state) use ($format, $onlyDate): ?string {
                $format = $component->evaluate($format, ['record' => $record, 'state' => $state]);
                $onlyDate = $component->evaluate($onlyDate, ['record' => $record, 'state' => $state]);
                $format ??= ($onlyDate ? __('persian::persian.date.format.simple') : __('persian::persian.date.format.time-simple'));

                return $format;
            });

            return $this;
        });

        DatePicker::mixin(new class implements FormjDateMacrosInterface {});
        DateTimePicker::mixin(new class implements FormjDateTimeMacrosInterface {});
    }
}
