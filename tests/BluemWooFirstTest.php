<?php

namespace Bluem\Tests\Phpunit\Tests;

use PHPUnit\Framework\TestCase;

define('BLUEM_WOO_BASE_PATH', dirname(__FILE__)."\..\..\\");

/**
 * Abstract base class for all BluemPHP unit tests.
 */
class BluemWooFirstTest extends TestCase
{
    public function testCanTest()
    {
        $this->assertTrue(true);
    }

    public function testAddGetMethods()
    {
        $this->assertTrue(true);
    }
}
