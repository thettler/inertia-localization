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

it('can remove special characters form name', function () {
    $translations = (new InertiaLocalizationTranslationMutator)->restructure([
        'de' => [
            'website' => [
                'some-minus' => 'test',
                'some.dots' => 'test',
                'some+plus' => 'test',
                'some<less' => 'test',
                'some>bigger' => 'test',
                'some=equal' => 'test',
                'sOme=equal' => 'test',
            ],
        ],
    ]);

    expect($translations->group('website')->getAllKeys())
        ->toContain('some_minus')
        ->toContain('some_dots')
        ->toContain('some_plus')
        ->toContain('some_less')
        ->toContain('some_bigger')
        ->toContain('sOme_equal');

});

it('can alter reserved js keywords', function () {
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

it('can work with deep arrays', function () {
    $translations = (new InertiaLocalizationTranslationMutator)->restructure([
        'de' => [
            'website' => [
                'we' => [
                    'go' => [
                        'very' => 'deep',
                    ],
                ],
            ],
        ],
    ]);

    expect($translations->group('website')->getAllOriginalKeys()[0])
        ->toBe('we.go.very');
});
