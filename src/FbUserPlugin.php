<?php

namespace Mortezamasumi\FbUser;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FbUserPlugin implements Plugin
{
    public function getId(): string
    {
        return 'fb-user';
    }

    public function register(Panel $panel): void
    {
        $panel->resorces([
            //
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
