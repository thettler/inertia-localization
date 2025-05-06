<?php

namespace Thettler\InertiaLocalization\Contracts;

use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;

interface Mutator
{
    public function restructure(array $rawTranslations): array;
}
