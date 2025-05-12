<?php

use Inertia\Inertia;

it('shares current locale', function () {
    $sharedProps = Inertia::getShared();
    expect($sharedProps)->toBeEmpty();

    app(\Thettler\InertiaLocalization\Middleware\InertiaTranslationsMiddleware::class)
        ->handle(request(), function () {
        });

    $sharedProps = Inertia::getShared();
    expect($sharedProps[config('inertia-localization.js.current_locale_key')]())
        ->toBe('en');

    session()->put(config('inertia-localization.js.current_locale_key'), 'de');

    app(\Thettler\InertiaLocalization\Middleware\InertiaTranslationsMiddleware::class)
        ->handle(request(), function () {
        });

    $sharedProps = Inertia::getShared();
    expect($sharedProps[config('inertia-localization.js.current_locale_key')]())
        ->toBe('de');
});
