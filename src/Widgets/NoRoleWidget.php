<?php

namespace Mortezamasumi\FbUser\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Mortezamasumi\FbUser\Models\User;

class NoRoleWidget extends Widget
{
    protected static ?int $sort = -9999;

    protected int|string|array $columnSpan = 'full';

    public function render(): View
    {
        /** @var view-string $view */
        $view = 'fb-user::no-role-widget';

        return view($view, $this->getViewData());
    }

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return ! ($user?->roles?->count() ?? 0);
    }
}
