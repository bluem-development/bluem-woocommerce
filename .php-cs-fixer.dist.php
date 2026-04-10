<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            // Scan both src/ and tests/ as root directories
            ->in([
                __DIR__ . '/src',
                __DIR__ . '/tests',
            ])
            ->append(glob(__DIR__ . '/bluem-*.php') ?: [])
            // Exclude by relative directory name (not absolute path)
            ->exclude([
                'vendor',
            ])
            // ->notPath([/* ... */])
            // ->ignoreDotFiles(false) // true by default in v3, false in v4 or future mode
            // ->ignoreVCS(true) // true by default
    )
;
