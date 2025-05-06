<?php

namespace Thettler\InertiaLocalization\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Thettler\InertiaLocalization\InertiaLocalization
 */
class InertiaLocalization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Thettler\InertiaLocalization\InertiaLocalization::class;
    }
}
