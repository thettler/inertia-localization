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

    expect($translations->group('website')->getAllKeys())
        ->toContain('nested_translation')
        ->toContain('with_attribute');

    $translations = (new InertiaLocalizationTranslationMutator(jsFunctionCase: JsFunctionCase::Snake))->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations->group('website')->getAllKeys())
        ->toContain('nested_translation')
        ->toContain('with_attribute');

    $translations = (new InertiaLocalizationTranslationMutator(jsFunctionCase: JsFunctionCase::Pascal))->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations->group('website')->getAllKeys())
        ->toContain('NestedTranslation')
        ->toContain('WithAttribute');

    $translations = (new InertiaLocalizationTranslationMutator(jsFunctionCase: JsFunctionCase::Camel))->restructure([
        'de' => [
            'website' => [
                'with_attribute' => 'test',
                'nested' => ['translation' => 'test'],
            ],
        ],
    ]);

    expect($translations->group('website')->getAllKeys())
        ->toContain('nestedTranslation')
        ->toContain('withAttribute');
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
        expect($translations->group('website')->getAllKeys())
            ->toContain($keyword.'_');
    }
});
