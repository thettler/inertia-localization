<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Thettler\InertiaLocalization\Contracts\Generator;
use Thettler\InertiaLocalization\Enums\JsFramework;
use Thettler\InertiaLocalization\Enums\Mode;

class InertiaLocalizationGenerator implements Generator
{
    public function __construct(
        protected JsFramework $jsFramework = JsFramework::Vue,
        protected array $locales = [],
        protected Mode $mode = Mode::Static,
    ) {}

    public function generate(string $path, Translations $translations): void
    {
        File::makeDirectory($path, recursive: true, force: true);

        File::put($path.'/utils.js', $this->generateUtilsJs());
        File::put($path.'/index.js', $this->generateIndexJs($translations));

        foreach ($translations->grouped() as $group => $groupTranslations) {
            File::put(
                $path."/{$group}.js",
                $this->generateTranslationFunctions($groupTranslations)
            );
        }
    }

    public function generateIndexJs(Translations $translations): string
    {
        return collect($translations->grouped())
            ->keys()
            ->map(fn (string $group) => "export * as {$group} from './{$group}.js';")
            ->implode("\n");
    }

    /**
     * @param  Translation[]  $groupTranslations
     */
    public function generateTranslationFunctions(array $groupTranslations): string
    {
        $code = Str::of("import { findTranslation } from './utils.js';")
            ->newLine()
            ->when(
                $this->mode === Mode::Dynamic,
                fn (Stringable $code) => $code->append($this->getInertiaPageImport())->newLine()
            )
            ->append(
                "/**
 * @typedef {{$this->getLocalType()}} Locale
 */"
            )
            ->newLine(2);

        foreach ($groupTranslations as $value) {
            $code = $code->append($this->generateTranslationFunction($value));
        }

        return $code->toString();
    }

    public function generateUtilsJs(): string
    {
        $stub = file_get_contents(__DIR__.'/templates/utils.stub.js');

        $imports = $this->getInertiaPageImport();
        $get_locale = $this->getInertiaPageProps().'.'.config('inertia-localization.js.current_locale_key');

        return Str::of($stub)
            ->replace('/*% imports %*/', $imports)
            ->replace('/*% locales %*/', $this->getLocalType())
            ->replace('/*% framework_specific_get_locale %*/', $get_locale)
            ->replace('/*% default_locale %*/', app()->getFallbackLocale())
            ->toString();
    }

    protected function generateTranslationFunction(Translation $translation): string
    {
        $stub = file_get_contents(__DIR__.'/templates/translationFunction.stub.js');
        $parameters = collect($translation->translations)
            ->reduce(function (array $carry, string $string) {
                preg_match_all('/:(\w+)/', $string, $matches);

                return array_unique(
                    array_map(fn (string $parameter) => strtolower($parameter), [...$carry, ...$matches[1]])
                );
            }, []);

        $parametersJsDoc = '';
        $parametersJsParameter = '';
        $parametersJsVariable = '';

        if (! empty($parameters)) {
            $parametersJsVariable = 'params';
            $parametersJsParameter = "{$parametersJsVariable} = {}, ";
            $parametersJsDoc .= " * @param {Object} {$parametersJsVariable}\n";
            $parametersJsDoc .= implode(
                "\n",
                array_map(fn (string $parameter) => " * @param {String} [{$parametersJsVariable}.{$parameter}]",
                    $parameters)
            ).PHP_EOL;
        }

        $sharedPropKey = config('inertia-localization.dynamic.shared_prop_key');

        return Str::of($stub)
            ->replace(
                '/*% translations %*/',
                $this->mode === Mode::Static ? json_encode($translation->translations) : $this->getInertiaPageProps(
                ).".{$sharedPropKey}[\"{$translation->group}.{$translation->originalKey}\"] || {}"
            )
            ->replace('/*% functionName %*/', $translation->key." /*{$translation->getFullOriginalKey()}*/")
            ->replace('/*% key %*/', $translation->originalKey)
            ->replace('/*% parameters_jsdoc %*/', $parametersJsDoc)
            ->replace('/*% parameters_param %*/', $parametersJsParameter)
            ->replace('/*% parameters %*/', $parametersJsVariable)
            ->newLine()
            ->toString();
    }

    protected function getLocalType(): string
    {
        return implode('|', array_map(fn (string $locale) => "\"{$locale}\"", $this->locales));
    }

    protected function getInertiaPageProps(): string
    {
        return match ($this->jsFramework) {
            JsFramework::Vue => 'usePage().props',
        };
    }

    protected function getInertiaPageImport(): string
    {
        return match ($this->jsFramework) {
            JsFramework::Vue => "import { usePage } from '@inertiajs/vue3'",
        };
    }
}
