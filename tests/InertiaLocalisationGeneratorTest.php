<?php

use Thettler\InertiaLocalization\InertiaLocalizationGenerator;

it('can generate the js files', function () {

    new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    )
        ->generate(__DIR__.'/fake_filesystem/translations', [
            'website' => [
                'my_translation' => [
                    'de' => 'Wert',
                    'en' => 'value',
                ],
                'my_other_translation' => [
                    'de' => 'Wert :attribute',
                    'en' => 'value :attribute',
                ],
            ],
        ]);

    expect(file_exists(__DIR__.'/fake_filesystem/translations/website.js'))->toBeTrue()
        ->and(file_exists(__DIR__.'/fake_filesystem/translations/utils.js'))->toBeTrue()
        ->and(file_exists(__DIR__.'/fake_filesystem/translations/index.js'))->toBeTrue();
})
    ->after(fn () => \Illuminate\Support\Facades\File::deleteDirectory(__DIR__.'/fake_filesystem/translations'));

it('can generate the utils.js', function () {
    $jsCode = new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    )
        ->generateUtilsJs();

    expect($jsCode)->toBeString()
        ->toContain('* @typedef {"de"|"en"} Locale')
        ->toContain("import { usePage } from '@inertiajs/vue3'")
        ->toContain('usePage().props.locale !== undefined')
        ->toContain('return usePage().props.locale;')
        ->toContain('return "en";');
});

it('can generate the index.js', function () {
    $jsCode = new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    )
        ->generateIndexJs([
            'group' => [],
            'website' => [],
        ]);

    expect($jsCode)->toBeString()
        ->toContain("export * as website from './website.js';")
        ->toContain("export * as group from './group.js';");
});

it('can generate the group.js for translations', function () {
    $jsCode = new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    )
        ->generateTranslationFunctions([
            'my_translation' => [
                'de' => 'Wert',
                'en' => 'value',
            ],
        ]);

    expect($jsCode)
        ->toBeString()
        ->toContain("import { findTranslation } from './utils.js';")
        ->toContain("export function my_translation(locale = undefined) {\n")
        ->toContain('"de":"Wert"')
        ->toContain('"en":"value"');
});

it('can generate the group.js for translation with attribute', function () {
    $jsCode = new InertiaLocalizationGenerator(
        locales: ['de', 'en'],
    )
        ->generateTranslationFunctions([
            'my_translation' => [
                'de' => 'Wert :attribute',
                'en' => 'value :attribute',
            ],
        ]);

    expect($jsCode)
        ->toBeString()
        ->toContain("import { findTranslation } from './utils.js';")
        ->toContain("* @param {Object} params\n")
        ->toContain("* @param {String} [params.attribute]\n")
        ->toContain("export function my_translation(params = {}, locale = undefined) {\n")
        ->toContain('"de":"Wert :attribute"')
        ->toContain('"en":"value :attribute"');
});
