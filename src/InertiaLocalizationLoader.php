<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Thettler\InertiaLocalization\Contracts\Loader;
use Thettler\InertiaLocalization\Contracts\Mutator;
use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;

class InertiaLocalizationLoader implements Loader
{
    public function __construct(
        protected Mutator $mutator,
        protected array $locales = [],
        protected array $ignoredGroups = [],
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

        return $this->mutator->restructure($translations);
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
}
