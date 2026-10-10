<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemRequestValidator;
use PHPUnit\Framework\TestCase;

final class BluemRequestValidatorTest extends TestCase
{
    public function testItPreservesTheCurrentPermissiveValidationContract(): void
    {
        $validator = new BluemRequestValidator();

        self::assertTrue($validator->isWellFormed(null));
        self::assertTrue($validator->isValid(['unexpected' => 'data']));
    }
}
