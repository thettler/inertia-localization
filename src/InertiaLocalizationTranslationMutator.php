<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Support\Str;
use Thettler\InertiaLocalization\Enums\JsFunctionCase;

class InertiaLocalizationTranslationMutator implements \Thettler\InertiaLocalization\Contracts\Mutator
{
    public const RESERVED_JS_KEYWORDS = [
        'enum',
        'let',
        'static',
        'implements',
        'interface',
        'package',
        'private',
        'protected',
        'public',
        'yield',
        'break',
        'case',
        'catch',
        'class',
        'const',
        'continue',
        'debugger',
        'default',
        'delete',
        'do',
        'else',
        'export',
        'extends',
        'false',
        'finally',
        'for',
        'function',
        'if',
        'import',
        'in',
        'instanceof',
        'new',
        'null',
        'return',
        'super',
        'switch',
        'this',
        'throw',
        'true',
        'try',
        'typeof',
        'var',
        'void',
        'while',
        'with',
        'abstract',
        'boolean',
        'byte',
        'char',
        'double',
        'final',
        'float',
        'goto',
        'int',
        'long',
        'native',
        'short',
        'synchronized',
        'throws',
        'transient',
        'volatile',
    ];

    public function __construct(
        protected JsFunctionCase $jsFunctionCase = JsFunctionCase::Snake,
        protected string $reservedKeywordSuffix = '_',
    ) {}

    public function restructure(array $rawTranslations): Translations
    {
        $translations = new Translations;
        $translationsArray = [];

        foreach ($rawTranslations as $locale => $translationGroups) {
            foreach ($translationGroups as $group => $rawGroupTranslations) {
                $groupTranslations = $this->flattenTranslations($rawGroupTranslations);
                $originalTranslationKeys = array_keys($this->flattenTranslations($rawGroupTranslations, '.'));
                $index = 0;
                foreach ($groupTranslations as $key => $value) {
                    $key = $this->modifyTranslationName($key, $locale);
                    $identifier = $group.'_'.$key;
                    $translationsArray[$identifier]['translations'][$locale] = $value;
                    $translationsArray[$identifier]['group'] = $group;
                    $translationsArray[$identifier]['key'] = $key;
                    $translationsArray[$identifier]['originalKey'] = $originalTranslationKeys[$index];
                    $index++;
                }
            }
        }

        foreach ($translationsArray as $translation) {
            $translations->add(
                new Translation(
                    ...$translation,
                )
            );
        }

        return $translations;
    }

    protected function modifyTranslationName(string $name, string $locale): string
    {
        $name = $this->cleanFunctionName($name, language: $locale);
        $translationName = match ($this->jsFunctionCase) {
            JsFunctionCase::Camel => Str::camel($name),
            JsFunctionCase::Pascal => Str::studly($name),
            default => $name,
        };

        if (in_array($translationName, self::RESERVED_JS_KEYWORDS)) {
            return $translationName.$this->reservedKeywordSuffix;
        }

        return $translationName;
    }

    protected function flattenTranslations(array|string $array, $keySeparator = '_'): string|array
    {
        if (! is_array($array)) {
            return $array;
        }

        foreach ($array as $name => $value) {
            $translations = $this->flattenTranslations($value);

            if (! is_array($translations)) {
                continue;
            }

            foreach ($translations as $key => $val) {
                $array[$name.$keySeparator.$key] = $val;
            }

            unset($array[$name]);
        }

        return $array;
    }

    protected function cleanFunctionName(string $name, $language = 'en')
    {
        $name = $language ? Str::ascii($name, $language) : $name;
        // Remove all characters that are not the separator, letters, numbers, or whitespace
        $name = preg_replace('![^'.preg_quote('_').'\pL\pN\s]+!u', '_', $name);

        // Replace all separator characters and whitespace by a single separator
        $name = preg_replace('!['.preg_quote('_').'\s]+!u', '_', $name);
        return trim($name, '_');
    }
}
