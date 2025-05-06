<?php

namespace Thettler\InertiaLocalization\Contracts;

interface Generator
{
    public function generate(string $path, array $translations): void;

    public function generateIndexJs(array $translations): string;

    public function generateTranslationFunctions(array $groupTranslations): string;

    public function generateUtilsJs(): string;
}
