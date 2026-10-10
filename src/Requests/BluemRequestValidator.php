<?php

namespace Bluem\Wordpress\Requests;

final class BluemRequestValidator
{
    public function isWellFormed($request): bool
    {
        // Keep the current permissive behavior until field validation is added.
        return true;
    }

    public function isValid($request): bool
    {
        return $this->isWellFormed($request);
    }
}
