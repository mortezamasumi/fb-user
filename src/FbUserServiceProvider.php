<?php

namespace Mortezamasumi\FbUser;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportTesting\Testable;
use Mortezamasumi\FbEssentials\Facades\FbEssentials;
use Mortezamasumi\FbUser\Macros\GridMacroServiceProvider;
use Mortezamasumi\FbUser\Resources\UserResource;
use Mortezamasumi\FbUser\Testing\TestsFbUser;
use Mortezamasumi\FbUser\Widgets\NoRoleWidget;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FbUserServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fb-user';
    public static string $viewNamespace = 'fb-user';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations();
            })
            ->hasConfigFile()
            ->hasMigrations($this->getMigrations())
            ->hasTranslations()
            ->hasViews();
    }

    public function packageRegistered()
    {
        // config(['filament-shield.shield_resource.navigation_sort' => 9980]);

        $this->app->register(GridMacroServiceProvider::class);
    }

    public function packageBooted(): void
    {
        FbEssentials::filamentShieldAddResource(
            UserResource::class,
            [
                'export',
                'create_role_on_import',
                'force_change_password',
            ],
            false
        );

        FbEssentials::filamentShieldExcludeWidget(NoRoleWidget::class);

        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        Testable::mixin(new TestsFbUser);
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_cache_table',
            'create_exports_table',
            'create_imports_table',
            'create_jobs_table',
            'create_notifications_table',
            'create_users_table',
            'create_permission_tables',
        ];
    }

    protected function getAssetPackageName(): ?string
    {
        return 'mortezamasumi/fb-user';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('fb-user-styles', __DIR__.'/../resources/dist/css/index.css'),
        ];
    }
}
