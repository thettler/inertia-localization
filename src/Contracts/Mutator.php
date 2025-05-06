<?php

namespace Thettler\InertiaLocalization\Contracts;

interface Mutator
{
    public function restructure(array $rawTranslations): array;
}
