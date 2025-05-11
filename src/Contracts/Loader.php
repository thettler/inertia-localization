<?php

namespace Thettler\InertiaLocalization\Contracts;

use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;
use Thettler\InertiaLocalization\Translations;

interface Loader
{
    /**
     * @throws FaultyConfigException
     */
    public function load(string $langPath): Translations;
}
