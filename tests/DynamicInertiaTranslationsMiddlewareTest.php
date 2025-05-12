<?php

use Inertia\Inertia;
use Thettler\InertiaLocalization\Enums\Mode;
use Thettler\InertiaLocalization\Middleware\DynamicInertiaTranslationsMiddleware;

it('includes all translations in Dev Env', function () {
    app()->instance('path.lang', \Pest\testDirectory('/fixtures/lang'));
    config()->set('app.env', 'local');
    config()->set('inertia-localization.mode', Mode::Dynamic);

    $sharedProps = Inertia::getShared();
    expect($sharedProps)->toBeEmpty();

    app(DynamicInertiaTranslationsMiddleware::class)
        ->handle(request(), function () {});

    $sharedProps = Inertia::getShared();
    expect($sharedProps[config('inertia-localization.dynamic.shared_prop_key')]())
        ->toEqual(
            [
                'group.key' => [
                    'en' => 'Value',
                ],
                'website.basic' => [
                    'en' => 'A basic string',
                ],
                'website.with_attribute' => [
                    'en' => 'A string with :attribute',
                ],
                'website.only_in_one_en' => [
                    'en' => 'I only exist in one language',
                ],
                'website.nested.translation' => [
                    'en' => 'A nested translation',
                ],
            ]
        );
});

it('includes all translations only in Dynamic mode', function () {
    app()->instance('path.lang', \Pest\testDirectory('/fixtures/lang'));
    config()->set('app.env', 'local');
    config()->set('inertia-localization.mode', Mode::Static);

    $sharedProps = Inertia::getShared();
    expect($sharedProps)->toBeEmpty();

    app(DynamicInertiaTranslationsMiddleware::class)
        ->handle(request(), function () {});

    $sharedProps = Inertia::getShared();
    expect($sharedProps)->toBeEmpty();
});

it('includes only included translations in any other env', function () {
    app()->instance('path.lang', \Pest\testDirectory('/fixtures/lang'));
    config()->set('inertia-localization.mode', Mode::Dynamic);
    config()->set('app.env', 'prod');
    config()->set('inertia-localization.dynamic.include', ['website.basic', 'website.nested.translation']);

    $sharedProps = Inertia::getShared();
    expect($sharedProps)->toBeEmpty();

    app(DynamicInertiaTranslationsMiddleware::class)
        ->handle(request(), function () {});

    $sharedProps = Inertia::getShared();
    expect($sharedProps[config('inertia-localization.dynamic.shared_prop_key')]())
        ->toEqual(
            [
                'website.basic' => [
                    'en' => 'A basic string',
                ],
                'website.nested.translation' => [
                    'en' => 'A nested translation',
                ],
            ]
        );
});

it('includes only translations from file', function () {
    app()->instance('path.lang', \Pest\testDirectory('/fixtures/lang'));
    config()->set('inertia-localization.mode', Mode::Dynamic);
    config()->set('app.env', 'prod');
    config()->set('inertia-localization.dynamic.include_path', \Pest\testDirectory('/fixtures/include-translations.json'));

    $sharedProps = Inertia::getShared();
    expect($sharedProps)->toBeEmpty();

    app(DynamicInertiaTranslationsMiddleware::class)
        ->handle(request(), function () {});

    $sharedProps = Inertia::getShared();
    expect($sharedProps[config('inertia-localization.dynamic.shared_prop_key')]())
        ->toEqual(
            [
                'website.basic' => [
                    'en' => 'A basic string',
                ],
                'website.nested.translation' => [
                    'en' => 'A nested translation',
                ],
            ]
        );
});
