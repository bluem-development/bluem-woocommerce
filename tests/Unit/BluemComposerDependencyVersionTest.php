<?php

namespace Unit;

use Bluem\Wordpress\Support\BluemComposerDependencyVersion;
use PHPUnit\Framework\TestCase;

final class BluemComposerDependencyVersionTest extends TestCase
{
    private string $lockPath;

    protected function setUp(): void
    {
        $this->lockPath = tempnam(sys_get_temp_dir(), 'bluem-composer-');
    }

    protected function tearDown(): void
    {
        unlink($this->lockPath);
    }

    public function testItReturnsTheLockedVersionForADependency(): void
    {
        file_put_contents($this->lockPath, json_encode([
            'packages' => [
                ['name' => 'example/one', 'version' => '1.2.3'],
                ['name' => 'bluem-development/bluem-php', 'version' => '2.7.1'],
            ],
        ], JSON_THROW_ON_ERROR));

        self::assertSame(
            '2.7.1',
            (new BluemComposerDependencyVersion($this->lockPath))
                ->getVersion('bluem-development/bluem-php')
        );
    }

    public function testMissingOrInvalidLockDataReturnsNull(): void
    {
        file_put_contents($this->lockPath, '{invalid json');
        $reader = new BluemComposerDependencyVersion($this->lockPath);

        self::assertNull($reader->getVersion('example/one'));

        file_put_contents($this->lockPath, json_encode(['packages' => []], JSON_THROW_ON_ERROR));
        self::assertNull($reader->getVersion('example/one'));
    }
}
