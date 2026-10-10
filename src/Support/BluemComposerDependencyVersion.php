<?php

namespace Bluem\Wordpress\Support;

final class BluemComposerDependencyVersion
{
    /** @var array<string, array<string, string>> Request-local, scoped to this vendor directory. */
    private static array $versions = [];

    public function __construct(private readonly string $installedVersionsPath)
    {
    }

    public function getVersion(string $dependencyName): ?string
    {
        if (!array_key_exists($this->installedVersionsPath, self::$versions)) {
            // Set the guard before reading: reentrant diagnostics must not read again.
            self::$versions[$this->installedVersionsPath] = [];
            if (is_file($this->installedVersionsPath) && is_readable($this->installedVersionsPath)) {
                // Composer generates this small array alongside the required autoloader.
                // Read our own vendor metadata; a global InstalledVersions registry may
                // report another WordPress plugin's copy of the same dependency.
                $installed = require $this->installedVersionsPath;
                $packages = is_array($installed) ? ($installed['versions'] ?? []) : [];
                foreach (is_array($packages) ? $packages : [] as $name => $package) {
                    if (is_string($name) && is_array($package) && is_string($package['pretty_version'] ?? null)) {
                        self::$versions[$this->installedVersionsPath][$name] = $package['pretty_version'];
                    }
                }
            }
        }

        // Missing metadata is unknown. Do not decode the lockfile or log from this
        // diagnostic path, which can run while the request is short of memory.
        return self::$versions[$this->installedVersionsPath][$dependencyName] ?? null;
    }
}
