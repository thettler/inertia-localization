<?php

test('can generate a translation.js file ', function () {
    app()->instance('path.lang', \Pest\testDirectory('/fixtures/lang'));
    config()->set('inertia-localization.js.path', __DIR__.'/fake_filesystem/translations');

    $this->artisan('inertia-localization:generate')
        ->assertSuccessful();
})->after(fn () => \Illuminate\Support\Facades\File::deleteDirectory(__DIR__.'/fake_filesystem/translations'));
