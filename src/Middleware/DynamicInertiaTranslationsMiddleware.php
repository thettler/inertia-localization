<?php

namespace Thettler\InertiaLocalization\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Thettler\InertiaLocalization\Contracts\Loader;
use Thettler\InertiaLocalization\Enums\Mode;
use Thettler\InertiaLocalization\Translation;

class DynamicInertiaTranslationsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (config('inertia-localization.mode') !== Mode::Dynamic) {
            return $next($request);
        }

        if (config('app.env') === 'local') {
            Inertia::share(config('inertia-localization.dynamic.shared_prop_key'), function () {
                return collect(
                    app(Loader::class)
                        ->load(app('path.lang'))
                        ->grouped()
                )
                    ->flatMap(fn (array $translations) => collect($translations)->mapWithKeys(
                        fn (Translation $translation) => [
                            $translation->getFullOriginalKey() => collect(
                                config('inertia-localization.locales')
                            )
                                ->mapWithKeys(
                                    fn (string $locale) => [
                                        $locale => trans(
                                            key: $translation->getFullOriginalKey(),
                                            locale: $locale
                                        ),
                                    ]
                                ),
                        ]
                    )
                    )
                    ->toArray();
            });

            return $next($request);
        }

        Inertia::share(config('inertia-localization.dynamic.shared_prop_key'), function () {
            return collect(
                [
                    ...config('inertia-localization.dynamic.include'),
                    ...(File::exists(config('inertia-localization.dynamic.include_path')) ? (File::json(
                        config('inertia-localization.dynamic.include_path')
                    ) ?? []) : []),
                ]
            )
                ->mapWithKeys(fn (string $translationKey) => [
                    $translationKey => collect(config('inertia-localization.locales'))->mapWithKeys(
                        fn (string $locale) => [
                            $locale => trans(
                                key: $translationKey,
                                locale: $locale
                            ),
                        ]
                    ),
                ]
                )->toArray();
        });

        return $next($request);
    }
}
