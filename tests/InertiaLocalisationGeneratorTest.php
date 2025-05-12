<?php

use Thettler\InertiaLocalization\InertiaLocalizationGenerator;
use Thettler\InertiaLocalization\Translation;
use Thettler\InertiaLocalization\Translations;

afterEach(function () {
    \Illuminate\Support\Facades\File::deleteDirectory(__DIR__.'/fake_filesystem/translations');
});

it('can generate the js files', function () {
    (new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    ))
        ->generate(
            __DIR__.'/fake_filesystem/translations',
            new Translations(
                new Translation(
                    key: 'my_translation',
                    originalKey: 'my_translation',
                    group: 'website',
                    translations: [
                        'de' => 'Wert',
                        'en' => 'value',
                    ]
                ),
                new Translation(
                    key: 'my_other_translation',
                    originalKey: 'my_other_translation',
                    group: 'website',
                    translations: [
                        'de' => 'Wert :attribute',
                        'en' => 'value :attribute',
                    ]
                ),
            )
        );

    expect(file_exists(__DIR__.'/fake_filesystem/translations/website.js'))->toBeTrue()
        ->and(file_exists(__DIR__.'/fake_filesystem/translations/utils.js'))->toBeTrue()
        ->and(file_exists(__DIR__.'/fake_filesystem/translations/index.js'))->toBeTrue();
});

it('can generate the utils.js', function () {
    $jsCode = (new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    ))
        ->generateUtilsJs();

    expect($jsCode)->toBeString()
        ->toContain('* @typedef {"de"|"en"} Locale')
        ->toContain("import { usePage } from '@inertiajs/vue3'")
        ->toContain('usePage().props.current_locale !== undefined')
        ->toContain('return usePage().props.current_locale;')
        ->toContain('return "en";');
});

it('can generate the index.js', function () {
    $jsCode = (new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    ))
        ->generateIndexJs(
            new Translations(
                new Translation(
                    key: 'my_translation',
                    originalKey: 'my_translation',
                    group: 'group',
                    translations: [
                        'de' => 'Wert',
                        'en' => 'value',
                    ]
                ),
                new Translation(
                    key: 'my_other_translation',
                    originalKey: 'my_other_translation',
                    group: 'website',
                    translations: [
                        'de' => 'Wert :attribute',
                        'en' => 'value :attribute',
                    ]
                ),
            )
        );

    expect($jsCode)->toBeString()
        ->toContain("export * as website from './website.js';")
        ->toContain("export * as group from './group.js';");
});

it('can generate the group.js for translations', function () {
    $jsCode = (new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    ))
        ->generateTranslationFunctions(
            [
                new Translation(
                    key: 'my_translation',
                    originalKey: 'my_translation',
                    group: 'group',
                    translations: [
                        'de' => 'Wert',
                        'en' => 'value',
                    ]
                ),
            ]

        );

    expect($jsCode)
        ->toBeString()
        ->toContain("import { findTranslation } from './utils.js';")
        ->toContain('export function my_translation /*group.my_translation*/(locale = undefined) {')
        ->toContain('my_translation.originalKey = "group.my_translation"')
        ->toContain('"de":"Wert"')
        ->toContain('"en":"value"');
});

it('can generate the group.js for translation with attribute', function () {
    $jsCode = (new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    ))
        ->generateTranslationFunctions([
            new Translation(
                key: 'my_translation',
                originalKey: 'my_translation',
                group: 'group',
                translations: [
                    'de' => 'Wert :attribute',
                    'en' => 'value :attribute',
                ]
            ),
        ]);

    expect($jsCode)
        ->toBeString()
        ->toContain("import { findTranslation } from './utils.js';")
        ->toContain('* @param {Object} params')
        ->toContain('* @param {String} [params.attribute]')
        ->toContain('export function my_translation /*group.my_translation*/(params = {}, locale = undefined) {')
        ->toContain('"de":"Wert :attribute"')
        ->toContain('"en":"value :attribute"');
});

it('can generate in dynamic mode', function () {
    $jsCode = (new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
        mode: \Thettler\InertiaLocalization\Enums\Mode::Dynamic,
    ))
        ->generateTranslationFunctions([
            new Translation(
                key: 'my_translation',
                originalKey: 'my.translation',
                group: 'group',
                translations: [
                    'de' => 'Wert :attribute',
                    'en' => 'value :attribute',
                ]
            ),
        ]);

    expect($jsCode)
        ->toBeString()
        ->toContain("import { usePage } from '@inertiajs/vue3'")
        ->toContain('usePage().props.translations["group.my.translation"] || {}');
});
