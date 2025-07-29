<?php

namespace Mortezamasumi\FbUser;

use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Mortezamasumi\FbUser\Macros\ExportMacroServiceProvider;
use Mortezamasumi\FbUser\Macros\FormMacroServiceProvider;
use Mortezamasumi\FbUser\Macros\InfolistMacroServiceProvider;
use Mortezamasumi\FbUser\Macros\TableMacroServiceProvider;
use Mortezamasumi\FbUser\Testing\TestsFbUser;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FbUserServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fb-user';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            })
            ->hasConfigFile()
            ->hasMigrations($this->getMigrations())
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->register(FormMacroServiceProvider::class);
        $this->app->register(TableMacroServiceProvider::class);
        $this->app->register(InfolistMacroServiceProvider::class);
        $this->app->register(ExportMacroServiceProvider::class);
    }

    public function packageBooted(): void
    {
        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__.'/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/fb-user/{$file->getFilename()}"),
                ], 'fb-user-stubs');
            }
        }

        // Testing
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
}
