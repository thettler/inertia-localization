<?php

namespace Thettler\InertiaLocalization\Contracts;

use Thettler\InertiaLocalization\Translations;

interface Generator
{
    public function generate(string $path, Translations $translations): void;

    public function generateIndexJs(Translations $translations): string;

    public function generateTranslationFunctions(array $groupTranslations): string;

    public function generateUtilsJs(): string;
}
