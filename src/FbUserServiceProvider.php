<?php

namespace Mortezamasumi\FbUser;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Features\SupportTesting\Testable;
use Mortezamasumi\FbUser\Resources\UserResource;
use Mortezamasumi\FbUser\Testing\TestsFbUser;
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
        config(['filament-shield.shield_resource.navigation_sort' => 9980]);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        config(['filament-shield.resources.manage' => [
            ...config('filament-shield.resources.manage'),
            UserResource::class => [
                'view',
                'view_any',
                'create',
                'update',
                'restore',
                'restore_any',
                'replicate',
                'reorder',
                'delete',
                'delete_any',
                'force_delete',
                'force_delete_any',
                'export',
                'create_role_on_import',
                'force_change_password',
            ]
        ]]);

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
