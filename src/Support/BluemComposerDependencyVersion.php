<?php

namespace Bluem\Wordpress\Support;

use JsonException;

final class BluemComposerDependencyVersion
{
    public function __construct(private readonly string $composerLockPath)
    {
    }

    public function getVersion(string $dependencyName): ?string
    {
        $contents = file_get_contents($this->composerLockPath);
        if ($contents === false) {
            return null;
        }

        try {
            $composerLock = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        foreach ($composerLock['packages'] ?? [] as $package) {
            if (($package['name'] ?? null) === $dependencyName) {
                return isset($package['version']) ? (string) $package['version'] : null;
            }
        }

        return null;
    }
}
