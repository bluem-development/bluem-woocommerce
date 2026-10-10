<?php

namespace Unit;

use Bluem\Wordpress\Support\BluemComposerDependencyVersion;
use PHPUnit\Framework\TestCase;

final class BluemComposerDependencyVersionTest extends TestCase
{
    private string $metadataPath;

    protected function setUp(): void
    {
        $this->metadataPath = tempnam(sys_get_temp_dir(), 'bluem-composer-');
    }

    protected function tearDown(): void
    {
        if (is_file($this->metadataPath)) {
            unlink($this->metadataPath);
        }
    }

    private function writeMetadata(array $metadata): void
    {
        file_put_contents($this->metadataPath, '<?php return ' . var_export($metadata, true) . ';');
    }

    public function testItReturnsTheLocalInstalledPrettyVersion(): void
    {
        $this->writeMetadata(['versions' => [
            'bluem-development/bluem-php' => ['pretty_version' => 'v2.7.3', 'version' => '2.7.3.0'],
        ]]);
        self::assertSame('v2.7.3', (new BluemComposerDependencyVersion($this->metadataPath))
            ->getVersion('bluem-development/bluem-php'));

        // The global Composer registry has 2.7.3: another vendor copy must stay isolated.
        $otherPath = tempnam(sys_get_temp_dir(), 'bluem-composer-');
        try {
            file_put_contents($otherPath, '<?php return ' . var_export(['versions' => [
                'bluem-development/bluem-php' => ['pretty_version' => '2.6.1'],
            ]], true) . ';');
            self::assertSame('2.6.1', (new BluemComposerDependencyVersion($otherPath))
                ->getVersion('bluem-development/bluem-php'));
        } finally {
            unlink($otherPath);
        }
    }

    public function testNewReadersReuseTheRequestCacheForHitsAndMisses(): void
    {
        $this->writeMetadata(['versions' => ['example/one' => ['pretty_version' => '1.2.3']]]);
        $reader = new BluemComposerDependencyVersion($this->metadataPath);
        self::assertSame('1.2.3', $reader->getVersion('example/one'));
        self::assertNull($reader->getVersion('example/missing'));
        // Any reread would throw, including a lookup of an uncached dependency.
        file_put_contents($this->metadataPath, '<?php throw new \RuntimeException("Read twice");');
        $nextReader = new BluemComposerDependencyVersion($this->metadataPath);
        self::assertSame('1.2.3', $nextReader->getVersion('example/one'));
        self::assertNull($nextReader->getVersion('example/missing'));
        self::assertNull($nextReader->getVersion('example/another'));
    }

    public function testMissingMetadataReturnsUnknownWithoutWarningsOrLockfileFallback(): void
    {
        unlink($this->metadataPath);
        set_error_handler(static function (int $severity, string $message): never {
            throw new \RuntimeException($message);
        });
        try {
            self::assertNull((new BluemComposerDependencyVersion($this->metadataPath))->getVersion('example/one'));
        } finally {
            restore_error_handler();
        }
    }

    public function testAbsentOrInvalidVersionDataReturnsNull(): void
    {
        $this->writeMetadata(['versions' => [
            'example/no-version' => ['version' => '1.2.3.0'],
            'example/invalid' => ['pretty_version' => []],
            'example/not-package' => false,
        ]]);
        $reader = new BluemComposerDependencyVersion($this->metadataPath);
        self::assertNull($reader->getVersion('example/no-version'));
        self::assertNull($reader->getVersion('example/invalid'));
        self::assertNull($reader->getVersion('example/not-package'));
        self::assertNull($reader->getVersion('example/missing'));
    }

    public function testInvalidPackageMapReturnsNullWithoutWarnings(): void
    {
        $this->writeMetadata(['versions' => false]);
        self::assertNull((new BluemComposerDependencyVersion($this->metadataPath))->getVersion('example/one'));
    }

    public function testNonArrayMetadataReturnsNull(): void
    {
        file_put_contents($this->metadataPath, '<?php return false;');
        self::assertNull((new BluemComposerDependencyVersion($this->metadataPath))->getVersion('example/one'));
    }

    public function testReentrantDetectionDoesNotReadMetadataAgain(): void
    {
        file_put_contents($this->metadataPath, <<<'METADATA'
<?php
$nested = (new \Bluem\Wordpress\Support\BluemComposerDependencyVersion(__FILE__))
    ->getVersion('example/one');
if ($nested !== null) {
    throw new \RuntimeException('Reentrant lookup should return unknown');
}
return ['versions' => ['example/one' => ['pretty_version' => '1.2.3']]];
METADATA);
        self::assertSame('1.2.3', (new BluemComposerDependencyVersion($this->metadataPath))->getVersion('example/one'));
    }

    public function testDetectionSucceedsWithLimitedHeadroomAndDoesNotGrowOnRepeatedCalls(): void
    {
        $root = dirname(__DIR__, 2);
        $command = escapeshellarg(PHP_BINARY) . ' -d memory_limit=32M -d opcache.enable_cli=0 '
            . escapeshellarg($root . '/tests/Fixtures/composer-version-memory.php') . ' '
            . escapeshellarg($root . '/src/Support/BluemComposerDependencyVersion.php') . ' '
            . escapeshellarg($root . '/vendor/composer/installed.php') . ' 2>&1';
        exec($command, $output, $exitCode);
        self::assertSame(0, $exitCode, implode("\n", $output));
        $result = implode("\n", $output);
        $lock = json_decode(file_get_contents($root . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
        $package = array_values(array_filter($lock['packages'], static fn(array $p): bool => $p['name'] === 'bluem-development/bluem-php'))[0];
        self::assertStringContainsString('version=' . $package['version'], $result);
        self::assertStringContainsString('repeated_delta=0', $result);
    }
}
