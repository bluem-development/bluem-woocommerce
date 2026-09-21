<?php

namespace Bluem\Wordpress\Settings;

use stdClass;

final class BluemConfigurationBuilder
{
    /**
     * Later feature definitions override earlier ones. Only declared keys enter
     * the API configuration; null saved values fall back just like missing keys.
     *
     * @param mixed $values Saved WordPress options (normally array|false).
     * @param array<string, array> ...$definitions Ordered feature schemas.
     */
    public function build($values, array ...$definitions): stdClass
    {
        $options = array_merge(...$definitions);
        $config = new stdClass();

        foreach ($options as $key => $option) {
            $config->$key = $values[$key] ?? ($option['default'] ?? '');
        }

        return $config;
    }
}
