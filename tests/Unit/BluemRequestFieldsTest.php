<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemRequestFields;
use PHPUnit\Framework\TestCase;

final class BluemRequestFieldsTest extends TestCase
{
    public function testItReturnsTheRequestStorageFieldCatalog(): void
    {
        self::assertSame([
            'id',
            'entrance_code',
            'transaction_id',
            'transaction_url',
            'user_id',
            'timestamp',
            'description',
            'type',
            'debtor_reference',
            'order_id',
            'payload',
        ], (new BluemRequestFields())->all());
    }
}
