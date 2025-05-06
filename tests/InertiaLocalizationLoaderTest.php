<?php

use Thettler\InertiaLocalization\Enums\JsFunctionCase;
use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;
use Thettler\InertiaLocalization\InertiaLocalizationLoader;

use function Pest\testDirectory;

it('can load translations and return them in the correct format', function () {
    $translations = (new InertiaLocalizationLoader(
        locales: ['de', 'en']
    ))
        ->load(testDirectory('fixtures/lang'));

    expect($translations)
        ->toBe([
            'group' => [
                'key' => [
                    'de' => 'Wert',
                    'en' => 'Value',
                ],
            ],
            'website' => [
                'basic' => [
                    'de' => 'Ein basic String',
                    'en' => 'A basic string',
                ],
                'with_attribute' => [
                    'de' => 'Ein string mit :attribute',
                    'en' => 'A string with :attribute',
                ],
                'only_in_one_de' => [
                    'de' => 'Ich existiere nur in einer Sprache',
                ],
                'nested_translation' => [
                    'de' => 'Eine verschachtelte Übersetzung',
                    'en' => 'A nested translation',
                ],
                'only_in_one_en' => [
                    'en' => 'I only exist in one language',
                ],
            ],
        ]);
});

it('can ignore groups', function () {
    $translations = (new InertiaLocalizationLoader(
        locales: ['de', 'en'],
        ignoredGroups: ['website']
    ))
        ->load(testDirectory('fixtures/lang'));

    expect($translations)
        ->toHaveCount(1)
        ->toBe([
            'group' => [
                'key' => [
                    'de' => 'Wert',
                    'en' => 'Value',
                ],
            ],
        ]);
});

it('can change translation keys to different keys', function () {
    $translations = (new InertiaLocalizationLoader(locales: ['en']))
        ->load(testDirectory('fixtures/lang'));

    expect($translations['website'])
        ->toHaveKey('nested_translation')
        ->toHaveKey('with_attribute');

    $translations = (new InertiaLocalizationLoader(
        locales: ['en'],
        jsFunctionCase: JsFunctionCase::Snake
    ))
        ->load(testDirectory('fixtures/lang'));

    expect($translations['website'])
        ->toHaveKey('nested_translation')
        ->toHaveKey('with_attribute');

    $translations = (new InertiaLocalizationLoader(
        locales: ['en'],
        jsFunctionCase: JsFunctionCase::Pascal
    ))
        ->load(testDirectory('fixtures/lang'));

    expect($translations['website'])
        ->toHaveKey('NestedTranslation')
        ->toHaveKey('WithAttribute');

    $translations = (new InertiaLocalizationLoader(
        locales: ['en'],
        jsFunctionCase: JsFunctionCase::Camel
    ))
        ->load(testDirectory('fixtures/lang'));

    expect($translations['website'])
        ->toHaveKey('nestedTranslation')
        ->toHaveKey('withAttribute');
});

it('throws error if lang directory does not exist', function () {
    (new InertiaLocalizationLoader)->load('lang/does/not/exist');
})->throws(FaultyConfigException::class, "Language directory 'lang/does/not/exist' does not exist.");
