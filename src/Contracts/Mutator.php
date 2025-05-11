<?php

namespace Thettler\InertiaLocalization\Contracts;

use Thettler\InertiaLocalization\Translations;

interface Mutator
{
    public function restructure(array $rawTranslations): Translations;
}
