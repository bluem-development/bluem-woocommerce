<?php

// Check the package actually built for SVN, not just the development lock.
$directory = $argv[1] ?? 'build';
$lock = json_decode(file_get_contents($directory . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
$installed = json_decode(file_get_contents($directory . '/vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
$expected = array_column($lock['packages'], 'name');
$actual = array_column($installed['packages'], 'name');
sort($expected);
sort($actual);
if ($installed['dev'] !== false || $expected !== $actual) {
    fwrite(STDERR, "Production package must contain exactly the runtime Composer dependencies.\n");
    exit(1);
}
foreach ($lock['packages-dev'] as $package) {
    if (file_exists($directory . '/vendor/' . $package['name'])) {
        fwrite(STDERR, 'Development dependency shipped: ' . $package['name'] . "\n");
        exit(1);
    }
}
foreach (['node_modules', 'tools', 'package.json', 'package-lock.json', '.github', 'docs'] as $path) {
    if (file_exists($directory . '/' . $path)) {
        fwrite(STDERR, 'Development artifact shipped: ' . $path . "\n");
        exit(1);
    }
}
echo "Production dependency isolation verified.\n";
