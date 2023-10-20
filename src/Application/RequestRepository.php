<?php

namespace Bluem\BluemPHP\Application;

use Bluem\BluemPHP\Domain\Request;
use Bluem\BluemPHP\Infrastructure\DatabaseService;
use Bluem\BluemPHP\Infrastructure\RequestFactory;

class RequestRepository
{
    private DatabaseService $databaseService;
    private string $requestTableName;

    public function __construct()
    {
        $this->databaseService = new DatabaseService();

        $this->requestTableName = "bluem_requests";
    }

    public function add($request_object): int
    {
        // validate request object
        if ( ! $this->validatedRequestObject( $request_object ) ) {
            return -1;
        }

        return $this->databaseService->insert(
            $this->requestTableName,
            $request_object
        );
    }

    private function validatedRequestObject( Request $request ): bool {
        // check if present
        // entrance_code
        // transaction_id
        // transaction_url
        // user_id
        // timestamp
        // description
        // type

        // optional fields
        // debtor_reference
        // order_id
        // payload

        // and well formed
        if ( ! $this->requestWellFormed( $request ) ) {
            return false;
        }

        return true;
    }

    private function requestWellFormed(Request $request): bool
    {
        // @todo: check all available fields on their format
        return true;
    }


}
