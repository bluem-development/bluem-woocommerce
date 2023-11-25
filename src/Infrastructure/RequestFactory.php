<?php

namespace Bluem\WooCommerce\Infrastructure;

use Bluem\WooCommerce\Domain\Request;

class RequestFactory
{
    public function fromArray(array $requestArray): Request
    {
        return new Request(
            $requestArray['entrance_code'],
            $requestArray['transaction_id'],
            $requestArray['transaction_url'],
            $requestArray['description'],
            $requestArray['debtor_reference'],
            $requestArray['environment'],
            $requestArray['user_id'],
            $requestArray['order_id'],
            $requestArray['payload']
        );
    }
}
