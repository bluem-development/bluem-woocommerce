<?php

namespace Bluem\Wordpress\Settings;

/** Resolves iDIN option definitions; WordPress adapters build the definitions. */
final class BluemIdentitySettings
{
    private readonly BluemOptionLookup $lookup;

    public function __construct(array $options)
    {
        $this->lookup = new BluemOptionLookup($options);
    }

    public function get($key): mixed
    {
        return $this->lookup->get($key);
    }
}
