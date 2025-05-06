<?php

use Thettler\InertiaLocalization\Enums\JsFunctionCase;
use Thettler\InertiaLocalization\InertiaLocalizationTranslationMutator;

it('can change translation keys to different keys', function () {
    $translations = (new InertiaLocalizationTranslationMutator)->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations['website'])
        ->toHaveKey('nested_translation')
        ->toHaveKey('with_attribute');

    $translations = (new InertiaLocalizationTranslationMutator(jsFunctionCase: JsFunctionCase::Snake))->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations['website'])
        ->toHaveKey('nested_translation')
        ->toHaveKey('with_attribute');

    $translations = (new InertiaLocalizationTranslationMutator(jsFunctionCase: JsFunctionCase::Pascal))->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations['website'])
        ->toHaveKey('NestedTranslation')
        ->toHaveKey('WithAttribute');

    $translations = (new InertiaLocalizationTranslationMutator(jsFunctionCase: JsFunctionCase::Camel))->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations['website'])
        ->toHaveKey('nestedTranslation')
        ->toHaveKey('withAttribute');
});

it('can flatten alter reserved js keywords', function () {
    $translations = (new InertiaLocalizationTranslationMutator)->restructure([
        'de' => [
            'website' => collect(InertiaLocalizationTranslationMutator::RESERVED_JS_KEYWORDS)
                ->mapWithKeys(fn (string $keyword) => [$keyword => 'test'])
                ->toArray(),
        ],
    ]);

    foreach (InertiaLocalizationTranslationMutator::RESERVED_JS_KEYWORDS as $keyword) {
        expect($translations['website'])
            ->toHaveKey($keyword.'_');
    }
});
