<?php

namespace Unit;

use Bluem\Wordpress\Presentation\BluemRequestGrouper;
use PHPUnit\Framework\TestCase;
use stdClass;

final class BluemRequestGrouperTest extends TestCase
{
    public function testItCreatesEnabledBucketsAndMapsLegacyPaymentsToIdeal(): void
    {
        $grouper = new BluemRequestGrouper();
        $idealRequest = (object) ['type' => 'ideal', 'id' => 1];
        $legacyPayment = (object) ['type' => 'payments', 'id' => 2];
        $identityRequest = (object) ['type' => 'identity', 'id' => 3];

        self::assertEquals(
            [
                'ideal' => [$idealRequest, $legacyPayment],
                'identity' => [$identityRequest],
                'mandates' => [],
            ],
            $grouper->group(
                [$idealRequest, $legacyPayment, $identityRequest],
                ['ideal', 'identity', 'mandates']
            )
        );
    }

    public function testRequestsForDisabledTypesRemainAddressable(): void
    {
        $request = new stdClass();
        $request->type = 'creditcard';

        self::assertSame(
            ['ideal' => [], 'creditcard' => [$request]],
            (new BluemRequestGrouper())->group([$request], ['ideal'])
        );
    }
}
