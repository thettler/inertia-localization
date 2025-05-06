<?php

use Thettler\InertiaLocalization\Enums\JsFunctionCase;
use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;
use Thettler\InertiaLocalization\InertiaLocalizationLoader;
use Thettler\InertiaLocalization\InertiaLocalizationTranslationMutator;

use function Pest\testDirectory;

it('can load translations and return them in the correct format', function () {
    $translations = (new InertiaLocalizationLoader(
        mutator: new InertiaLocalizationTranslationMutator,
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
        mutator: new InertiaLocalizationTranslationMutator,
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

it('throws error if lang directory does not exist', function () {
    (new InertiaLocalizationLoader(
        mutator: new InertiaLocalizationTranslationMutator,
    ))
        ->load('lang/does/not/exist');
})->throws(FaultyConfigException::class, "Language directory 'lang/does/not/exist' does not exist.");
