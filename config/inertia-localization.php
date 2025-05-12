<?php

use Thettler\InertiaLocalization\Enums\Mode;

return [
    'mode' => Mode::Static,
    'locales' => ['en'],
    'ignored_groups' => [],
    'current_locale_session_key' => 'current_locale',
    'js' => [
        'framework' => \Thettler\InertiaLocalization\Enums\JsFramework::Vue,
        'function_case' => \Thettler\InertiaLocalization\Enums\JsFunctionCase::Snake,
        'path' => resource_path('js/translations'),
        'reserved_keyword_suffix' => '_',
        'current_locale_key' => 'current_locale',
    ],
    'dynamic' => [
        'shared_prop_key' => 'translations',
        'include' => [],
        'include_path' => storage_path('include-translations.json'),
    ],
];
