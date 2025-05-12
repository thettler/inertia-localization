<?php

use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;
use Thettler\InertiaLocalization\InertiaLocalizationLoader;
use Thettler\InertiaLocalization\InertiaLocalizationTranslationMutator;
use Thettler\InertiaLocalization\Translation;
use Thettler\InertiaLocalization\Translations;

use function Pest\testDirectory;

it('can load translations and return them in the correct format', function () {
    $translations = (new InertiaLocalizationLoader(
        mutator: new InertiaLocalizationTranslationMutator,
        locales: ['de', 'en']
    ))
        ->load(testDirectory('fixtures/lang'));
    expect($translations)
        ->toEqual(
            new Translations(
                new Translation(
                    key: 'key',
                    originalKey: 'key',
                    group: 'group',
                    translations: [
                        'de' => 'Wert',
                        'en' => 'Value',
                    ]
                ),
                new Translation(
                    key: 'basic',
                    originalKey: 'basic',
                    group: 'website',
                    translations: [
                        'de' => 'Ein basic String',
                        'en' => 'A basic string',
                    ]
                ),
                new Translation(
                    key: 'with_attribute',
                    originalKey: 'with_attribute',
                    group: 'website',
                    translations: [
                        'de' => 'Ein string mit :attribute',
                        'en' => 'A string with :attribute',
                    ]
                ),
                new Translation(
                    key: 'only_in_one_de',
                    originalKey: 'only_in_one_de',
                    group: 'website',
                    translations: [
                        'de' => 'Ich existiere nur in einer Sprache',
                    ]
                ),
                new Translation(
                    key: 'nested_translation',
                    originalKey: 'nested.translation',
                    group: 'website',
                    translations: [
                        'de' => 'Eine verschachtelte Übersetzung',
                        'en' => 'A nested translation',
                    ]
                ),
                new Translation(
                    key: 'only_in_one_en',
                    originalKey: 'only_in_one_en',
                    group: 'website',
                    translations: [
                        'en' => 'I only exist in one language',

                    ]
                ),
            ),
        );
});

it('can ignore groups', function () {
    $translations = (new InertiaLocalizationLoader(
        mutator: new InertiaLocalizationTranslationMutator,
        locales: ['de', 'en'],
        ignoredGroups: ['website']
    ))
        ->load(testDirectory('fixtures/lang'));

    expect($translations)
        ->toEqual(
            new Translations(
                new Translation(
                    key: 'key',
                    originalKey: 'key',
                    group: 'group',
                    translations: [
                        'de' => 'Wert',
                        'en' => 'Value',
                    ]
                ),
            ),
        );
});

it('throws error if lang directory does not exist', function () {
    (new InertiaLocalizationLoader(
        mutator: new InertiaLocalizationTranslationMutator,
    ))
        ->load('lang/does/not/exist');
})->throws(FaultyConfigException::class, "Language directory 'lang/does/not/exist' does not exist.");
