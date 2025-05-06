<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Thettler\InertiaLocalization\Contracts\Loader;
use Thettler\InertiaLocalization\Enums\JsFunctionCase;
use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;

class InertiaLocalizationLoader implements Loader
{
    public function __construct(
        protected array $locales = [],
        protected array $ignoredGroups = [],
        protected JsFunctionCase $jsFunctionCase = JsFunctionCase::Snake,
    ) {}

    public function load(string $langPath): array
    {
        if (! File::exists($langPath)) {
            throw new FaultyConfigException("Language directory '{$langPath}' does not exist.");
        }

        $localeDirectories = array_filter(
            File::directories($langPath),
            fn (string $localeDirectory) => Str::endsWith($localeDirectory, $this->locales)
        );
        $translations = collect($localeDirectories)
            ->mapWithKeys(fn (string $localeDirectory) => [
                File::name($localeDirectory) => $this->loadDirectory($localeDirectory),
            ])
            ->toArray();

        return $this->restructureTranslations($translations);
    }

    protected function loadDirectory(string $path): array
    {
        return collect(File::allFiles($path))
            ->filter(
                fn (SplFileInfo $file) => File::extension($file) === 'php' && ! in_array(
                    $file->getFilenameWithoutExtension(),
                    $this->ignoredGroups
                )
            )
            ->mapWithKeys(fn (SplFileInfo $file) => [
                $file->getFilenameWithoutExtension() => require $file->getPathname(),
            ])
            ->toArray();
    }

    protected function restructureTranslations(array $rawTranslations): array
    {
        $translations = [];
        foreach ($rawTranslations as $locale => $translationGroups) {
            foreach ($translationGroups as $group => $groupTranslations) {
                $groupTranslations = $this->flattenTranslations($groupTranslations);

                foreach ($groupTranslations as $key => $value) {
                    $translations[$group][$this->modifyTranslationName($key)][$locale] = $value;
                }
            }
        }

        return $translations;
    }

    protected function modifyTranslationName(string $name): string
    {
        return match ($this->jsFunctionCase) {
            JsFunctionCase::Camel => Str::camel($name),
            JsFunctionCase::Pascal => Str::studly($name),
            default => $name,
        };
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
}
