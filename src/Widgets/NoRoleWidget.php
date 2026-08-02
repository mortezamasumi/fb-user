<?php

namespace Mortezamasumi\FbUser\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbUser\Models\User;

class NoRoleWidget extends Widget
{
    /** @var view-string */
    protected string $view = 'fb-user::no-role-widget';

    protected static ?int $sort = -9999;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return ! ($user?->roles?->count() ?? 0);
    }
}
