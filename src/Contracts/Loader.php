<?php

namespace Thettler\InertiaLocalization\Contracts;

use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;

interface Loader
{
    /**
     * @throws FaultyConfigException
     */
    public function load(string $langPath): array;
}
