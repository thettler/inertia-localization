<?php

namespace Thettler\InertiaLocalization;

class Translation
{
    public function __construct(
        public readonly string $key,
        public readonly string $originalKey,
        public readonly string $group,
        public readonly array $translations,
    ) {}

    public function getFullOriginalKey(): string
    {
        return $this->group.'.'.$this->originalKey;
    }
}
