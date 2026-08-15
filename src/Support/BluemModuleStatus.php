<?php

namespace Bluem\Wordpress\Support;

final class BluemModuleStatus
{
    public function __construct(private readonly array|false $options)
    {
    }

    public function isEnabled(string $module): bool
    {
        if ($this->options === false) {
            return false;
        }

        $key = $module . '_enabled';

        return ! isset($this->options[$key]) || $this->options[$key] === '1';
    }
}
