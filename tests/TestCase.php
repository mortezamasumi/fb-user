<?php

namespace Mortezamasumi\FbUser\Tests;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mortezamasumi\FbAuth\FbAuthPlugin;
use Mortezamasumi\FbAuth\FbAuthServiceProvider;
use Mortezamasumi\FbEssentials\FbEssentialsPlugin;
use Mortezamasumi\FbEssentials\FbEssentialsServiceProvider;
use Mortezamasumi\FbProfile\FbProfilePlugin;
use Mortezamasumi\FbProfile\FbProfileServiceProvider;
use Mortezamasumi\FbUser\FbUserPlugin;
use Mortezamasumi\FbUser\FbUserServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Spatie\Permission\PermissionServiceProvider;

class TestCase extends TestbenchTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('/')
                ->login()
                ->default()
                ->plugins([
                    FbEssentialsPlugin::make(),
                    FbAuthPlugin::make(),
                    FbProfilePlugin::make(),
                    FbUserPlugin::make(),
                ])
                ->authMiddleware([
                    Authenticate::class,
                ])
        );
    }

    protected function defineDatabaseMigrations()
    {
        $this->artisan('vendor:publish', ['--tag' => 'fb-user-migrations']);
    }

    protected function getPackageProviders($app)
    {
        return [
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider::class,
            \Orchestra\Workbench\WorkbenchServiceProvider::class,
            PermissionServiceProvider::class,
            FbEssentialsServiceProvider::class,
            FbAuthServiceProvider::class,
            FbProfileServiceProvider::class,
            FbUserServiceProvider::class,
        ];
    }
}
