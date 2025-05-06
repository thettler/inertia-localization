<?php

return [
    'locales' => [
        config('app.locale'),
        config('app.fallback_locale'),
    ],
    'ignored_groups' => [],
    'js' => [
        'framework' => \Thettler\InertiaLocalization\Enums\JsFramework::Vue,
        'function_case' => \Thettler\InertiaLocalization\Enums\JsFunctionCase::Snake,
        'path' => resource_path('js/translations'),
        'reserved_keyword_suffix' => '_',
    ],
];
