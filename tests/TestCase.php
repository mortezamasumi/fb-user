<?php

namespace Mortezamasumi\FbUser\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Mortezamasumi\FbAuth\FbAuthPlugin;
use Mortezamasumi\FbAuth\FbAuthServiceProvider;
use Mortezamasumi\FbEssentials\FbEssentialsPlugin;
use Mortezamasumi\FbEssentials\FbEssentialsServiceProvider;
use Mortezamasumi\FbProfile\FbProfilePlugin;
use Mortezamasumi\FbProfile\FbProfileServiceProvider;
use Mortezamasumi\FbUser\FbUserPlugin;
use Mortezamasumi\FbUser\FbUserServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Orchestra\Workbench\WorkbenchServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
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
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            WorkbenchServiceProvider::class,
            PermissionServiceProvider::class,
            FbEssentialsServiceProvider::class,
            FbAuthServiceProvider::class,
            FbProfileServiceProvider::class,
            FbUserServiceProvider::class,
        ];
    }
}
