<?php

namespace Mortezamasumi\FbUser;

use Filament\Contracts\Plugin;
use Filament\Schemas\Components\Form;
use Filament\Panel;
use Mortezamasumi\FbUser\Resources\UserResource;
use Mortezamasumi\FbUser\Widgets\NoRoleWidget;

class FbUserPlugin implements Plugin
{
    public function getId(): string
    {
        return 'fb-user';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                UserResource::class,
            ])
            ->widgets([
                NoRoleWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
