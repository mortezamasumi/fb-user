<?php

namespace Mortezamasumi\FbUser\Macros;

use Filament\Infolists\Components\Component;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Mortezamasumi\Persian\Facades\Persian;
use Closure;

/**
 * Interface declaring Table macros for IDE support
 *
 * @method static Component jDate(string|Closure|null $Tableat, ?string $timezone) jDate apply
 * @method static Component jDateTime(string|Closure|null $Tableat, ?string $timezone, bool|Closure $onlyDate) jDateTime apply
 * @method static Component localeDigit(?string $forceLocale) current locale apply
 */
interface InfolistTextEntryMacrosInterface {}

class InfolistMacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        TextEntry::macro('jDate', function (string|Closure|null $format = null, ?string $timezone = null, ?string $forceLocale = null): TextEntry {
            /** @var TextEntry $this */
            $this->jDateTime($format, $timezone, $forceLocale, true);

            return $this;
        });

        TextEntry::macro('jDateTime', function (string|Closure|null $format = null, ?string $timezone = null, ?string $forceLocale = null, bool|Closure $onlyDate = false): TextEntry {
            /** @var TextEntry $this */
            $this->formatStateUsing(static function (TextEntry $component, Model $record, $state) use ($format, $timezone, $forceLocale, $onlyDate): ?string {
                if (blank($state)) {
                    return null;
                }

                $format = $component->evaluate($format, ['record' => $record, 'state' => $state]);
                $onlyDate = $component->evaluate($onlyDate, ['record' => $record, 'state' => $state]);
                $format ??= ($onlyDate ? __('persian::persian.date.format.simple') : __('persian::persian.date.format.time-simple'));

                return Persian::jDateTime($format, $state, $timezone, $forceLocale);
            });

            return $this;
        });

        TextEntry::macro('localeDigit', function (?string $forceLocale = null): TextEntry {
            /** @var TextEntry $this */
            $this->formatStateUsing(static fn (mixed $state) => in_array(gettype($state), ['integer', 'double', 'string']) ? Persian::digit($state, $forceLocale) : $state);

            return $this;
        });

        TextEntry::mixin(new class implements InfolistTextEntryMacrosInterface {});
    }
}
