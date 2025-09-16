<?php

namespace Mortezamasumi\FbUser\Macros;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;
use Closure;

/**
 * Interface declaring Table macros for IDE support
 *
 * @method static Column jDate(string|Closure|null $Tableat, ?string $timezone) jDate apply
 * @method static Column jDateTime(string|Closure|null $Tableat, ?string $timezone, bool|Closure $onlyDate) jDateTime apply
 * @method static Column localeDigit(?string $forceLocale) current locale apply
 */
interface GridMacrosInterface {}

class GridMacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Grid::macro('hasRole', function (string $role, string $roleAttributeName = 'roles'): Grid {
            /** @var Grid $this */
            $this->visible(
                fn (Get $get) => in_array(Role::findByName($role)?->id, $get($roleAttributeName))
            );

            return $this;
        });

        TextColumn::mixin(new class implements GridMacrosInterface {});
    }
}
