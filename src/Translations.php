<?php

namespace Thettler\InertiaLocalization;

class Translations
{
    public array $translations;

    public function __construct(Translation ...$translations)
    {
        $this->translations = $translations;
    }

    public function add(Translation $translation): self
    {
        $this->translations[] = $translation;

        return $this;
    }

    public function group(string $group): Translations
    {
        return new Translations(
            ...array_filter($this->translations, fn (Translation $translation) => $translation->group === $group)
        );
    }

    public function grouped(): array
    {
        return collect($this->translations)->groupBy('group')->toArray();
    }

    public function getAllKeys(): array
    {
        return array_map(fn (Translation $translation) => $translation->key, $this->translations);
    }
    public function getAllOriginalKeys(): array
    {
        return array_map(fn (Translation $translation) => $translation->originalKey, $this->translations);
    }
}
