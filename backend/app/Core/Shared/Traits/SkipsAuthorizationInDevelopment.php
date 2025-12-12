<?php

namespace App\Core\Shared\Traits;

trait SkipsAuthorizationInDevelopment
{
    /**
     * Prüft ob Authorization in der Development-Umgebung übersprungen werden soll
     */
    protected function shouldSkipAuthorization(): bool
    {
        return config('app.skip_authorization', false);
    }

    /**
     * Wrapper für Authorization-Checks der in Development immer true zurückgibt
     */
    protected function authorize(callable $check): bool
    {
        if ($this->shouldSkipAuthorization()) {
            return true;
        }

        return $check();
    }
}