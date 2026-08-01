<?php

namespace Bluem\Wordpress\Requests;

final class BluemRequestFields
{
    /**
     * @return string[]
     */
    public function all(): array
    {
        return [
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
        ];
    }
}
