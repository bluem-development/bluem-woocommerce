<?php

namespace Bluem\Wordpress\Presentation;

final class BluemAdminTabResolver
{
    public function resolve(?string $tab, $default = null): mixed
    {
        return $tab ?? $default;
    }
}
