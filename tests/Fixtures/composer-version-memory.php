<?php

// Standalone subprocess: no WordPress, Sentry, or PHPUnit allocations.
require $argv[1];

$reader = new Bluem\Wordpress\Support\BluemComposerDependencyVersion($argv[2]);
$padding = [str_repeat('x', 8 * 1024 * 1024)];
// Leave about 512 KiB in the current allocator, then prevent another arena.
while (memory_get_usage(true) - memory_get_usage() > 512 * 1024) {
    $padding[] = str_repeat('x', 16 * 1024);
}
ini_set('memory_limit', (string) (memory_get_usage(true) + 64 * 1024));
$before = memory_get_usage();
memory_reset_peak_usage();
printf("before=%d allocated=%d limit=%s\n", $before, memory_get_usage(true), ini_get('memory_limit'));
$version = $reader->getVersion('bluem-development/bluem-php');
$firstPeak = memory_get_peak_usage() - $before;
$afterFirst = memory_get_usage();
for ($i = 0; $i < 1000; ++$i) {
    $version = (new Bluem\Wordpress\Support\BluemComposerDependencyVersion($argv[2]))
        ->getVersion('bluem-development/bluem-php');
}
printf("version=%s peak_delta=%d retained=%d repeated_delta=%d\n", $version ?? 'unknown', $firstPeak, $afterFirst - $before, memory_get_usage() - $afterFirst);
