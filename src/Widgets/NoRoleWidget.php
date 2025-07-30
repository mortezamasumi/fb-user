<?php

namespace Mortezamasumi\FbUser\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NoRoleWidget extends Widget
{
    protected string $view = 'fb-user::no-role-widget';
    protected static ?int $sort = -9999;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! Auth::user()->roles->count();
    }
}
