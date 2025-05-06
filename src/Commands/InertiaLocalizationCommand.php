<?php

namespace Thettler\InertiaLocalization\Commands;

use Illuminate\Console\Command;
use Thettler\InertiaLocalization\Contracts\Generator;
use Thettler\InertiaLocalization\Contracts\Loader;
use Thettler\InertiaLocalization\Exceptions\FaultyConfigException;

class InertiaLocalizationCommand extends Command
{
    public $signature = 'inertia-localization:generate';

    public $description = 'My command';

    public function handle()
    {
        try {
            $translations = app(Loader::class)
                ->load(app('path.lang'));
        } catch (FaultyConfigException $exception) {
            $this->error($exception->getMessage());

            return static::FAILURE;
        }

        app(Generator::class)
            ->generate(config('inertia-localization.js.path'), $translations);

        return static::SUCCESS;
    }
}
