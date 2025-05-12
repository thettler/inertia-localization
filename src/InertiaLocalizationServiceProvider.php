<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Contracts\Foundation\Application;
use Inertia\Inertia;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Thettler\InertiaLocalization\Commands\InertiaLocalizationCommand;
use Thettler\InertiaLocalization\Contracts\Generator;
use Thettler\InertiaLocalization\Contracts\Loader;
use Thettler\InertiaLocalization\Contracts\Mutator;

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
            ->hasCommand(InertiaLocalizationCommand::class);
    }

    public function registeringPackage()
    {
        $this->app->singleton(Mutator::class, fn (Application $app) => new InertiaLocalizationTranslationMutator(
            jsFunctionCase: config('inertia-localization.js.function_case'),
            reservedKeywordSuffix: config('inertia-localization.js.reserved_keyword_suffix'),
        ));

        $this->app->singleton(Loader::class, fn (Application $app) => new InertiaLocalizationLoader(
            locales: config('inertia-localization.locales'),
            ignoredGroups: config('inertia-localization.ignored_groups'),
            mutator: app(Mutator::class)
        ));

        $this->app->singleton(Generator::class, fn (Application $app) => new InertiaLocalizationGenerator(
            jsFramework: config('inertia-localization.js.framework'),
            locales: config('inertia-localization.locales'),
            mode: config('inertia-localization.mode')
        ));
    }

    public function packageBooted()
    {
        Inertia::share('translations', function () {
            return collect(
                app(Loader::class)
                    ->load(app('path.lang'))
                    ->grouped()
            )
                ->map(fn (array $translations) => collect($translations)->mapWithKeys(
                    fn (Translation $translation) => [
                        $translation->originalKey => collect(config('inertia-localization.locales'))->mapWithKeys(
                            fn (string $locale) => [$locale => trans(
                                key: $translation->getFullOriginalKey(),
                                locale: $locale
                            )]
                        ),
                    ]
                )
                )
                ->toArray();
        });
    }
}
