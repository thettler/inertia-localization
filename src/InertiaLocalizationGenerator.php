<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Support\Str;
use Thettler\InertiaLocalization\Enums\JsFramework;

class InertiaLocalizationGenerator implements \Thettler\InertiaLocalization\Contracts\Generator
{
    public function __construct(
        protected JsFramework $jsFramework = JsFramework::Vue,
        protected array $locales = [],
    ) {}

    public function generate(string $path, array $translations): void
    {
        \Illuminate\Support\Facades\File::makeDirectory($path, recursive: true, force: true);

        \Illuminate\Support\Facades\File::put($path.'/utils.js', $this->generateUtilsJs());
        \Illuminate\Support\Facades\File::put($path.'/index.js', $this->generateIndexJs($translations));
        foreach ($translations as $group => $groupTranslations) {
            \Illuminate\Support\Facades\File::put(
                $path."/{$group}.js",
                $this->generateTranslationFunctions($groupTranslations)
            );
        }
    }

    public function generateIndexJs(array $translations): string
    {
        return collect($translations)
            ->keys()
            ->map(fn (string $group) => "export * as {$group} from './{$group}.js';")
            ->implode("\n");
    }

    public function generateTranslationFunctions(array $groupTranslations): string
    {
        $code = Str::of("import { findTranslation } from './utils.js';")
            ->newLine()
            ->append(
                "/**
 * @typedef {{$this->getLocalType()}} Locale
 */"
            )
            ->newLine(2);

        foreach ($groupTranslations as $key => $value) {
            $code = $code->append($this->generateTranslationFunction($key, $value));
        }

        return $code->toString();
    }

    public function generateUtilsJs(): string
    {
        $stub = file_get_contents(__DIR__.'/templates/utils.stub.js');

        $imports = match ($this->jsFramework) {
            JsFramework::Vue => "import { usePage } from '@inertiajs/vue3'",
        };

        $get_locale = match ($this->jsFramework) {
            JsFramework::Vue => 'usePage().props.locale',
        };

        return Str::of($stub)
            ->replace('/*% imports %*/', $imports)
            ->replace('/*% locales %*/', $this->getLocalType())
            ->replace('/*% framework_specific_get_locale %*/', $get_locale)
            ->replace('/*% default_locale %*/', app()->getFallbackLocale())
            ->toString();
    }

    protected function generateTranslationFunction(string $key, array $value): string
    {
        $stub = file_get_contents(__DIR__.'/templates/translationFunction.stub.js');
        $parameters = collect($value)
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

        return Str::of($stub)
            ->replace('/*% translations %*/', json_encode($value))
            ->replace('/*% key %*/', $key)
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
}
