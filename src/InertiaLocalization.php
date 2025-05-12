<?php

namespace Thettler\InertiaLocalization;

use Illuminate\Support\Facades\App;

class InertiaLocalization
{
    public function setCurrentLocale(string $locale): self
    {
        \Illuminate\Support\Facades\Session::put(
            config('inertia-localization.current_locale_session_key'),
            $locale
        );

        App::setLocale($locale);

        return $this;
    }

    public function currentLocale(): string
    {
        return \Illuminate\Support\Facades\Session::get(
            config('inertia-localization.current_locale_session_key'),
            config('app.locale')
        );
    }
}
