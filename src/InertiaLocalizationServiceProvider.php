<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Thettler\InertiaLocalization\Commands\InertiaLocalizationCommand;
use Thettler\InertiaLocalization\Contracts\Generator;
use Thettler\InertiaLocalization\Contracts\Loader;

class InertiaLocalizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('inertia-localization')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_inertia_localization_table')
            ->hasCommand(InertiaLocalizationCommand::class);
    }

    public function registeringPackage()
    {
        $this->app->singleton(Loader::class, fn (Application $app) => new InertiaLocalizationLoader(
            locales: config('inertia-localization.locales'),
            ignoredGroups: config('inertia-localization.ignored_groups'),
            jsFunctionCase: config('inertia-localization.js.function_case')
        ));

        $this->app->singleton(Generator::class, fn (Application $app) => new InertiaLocalizationGenerator(
            jsFramework: config('inertia-localization.js.framework'),
            locales: config('inertia-localization.locales'),
        ));
    }
}
