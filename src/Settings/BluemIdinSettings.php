<?php

namespace Bluem\Wordpress\Settings;

final class BluemIdinSettings
{
    public function __construct(private readonly array $options)
    {
    }

    public function get($key): mixed
    {
        return array_key_exists($key, $this->options)
            ? $this->options[$key]
            : false;
    }
}
