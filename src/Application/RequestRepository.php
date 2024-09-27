<?php

namespace Bluem\WooCommerce\Application;

use Bluem\BluemPHP\Domain\Request;
use Bluem\BluemPHP\Infrastructure\DatabaseService;
use Bluem\BluemPHP\Infrastructure\RequestFactory;
use JsonException;

class RequestRepository
{
    private DatabaseService $databaseService;
    private RequestFactory $requestFactory;
    private string $requestTableName = "bluem_requests";
    private string $requestLinksTableName = "bluem_requests_links";
    private string $logTableName = "bluem_requests_log";
    private WP_User $currentUser;

    public function __construct()
    {

        $this->databaseService = new DatabaseService();
        $this->requestFactory = new RequestFactory();
        $this->currentUser = wp_get_current_user();
    }

    public function add(array $request_array): ?Request
    {
        $request_object = $this->requestFactory->fromArray($request_array);

        // validate request object
        if ( ! $this->validatedRequestObject( $request_object ) ) {
            return null;
        }

        $inserted = $this->databaseService->insert(
            $this->requestTableName,
            $request_object->toArray()
        );

        if($inserted !== -1) {
            $result = $request_object->withId($this->databaseService->getInsertedId());

            if ( isset( $result->orderId )
                && $result->orderId !== ""
            ) {
                $this->addLinkToRequest($result->id, $result->orderId);
            }

            $this->addRequestLogItem(
                $result->id,
                "Created request"
            );
            return $result;
        }

        return null;
    }

    public function addLinkToRequest(int $requestId, int $itemId): void
    {
        $this->databaseService->insert($this->requestLinksTableName, [
            'request_id' => $requestId,
            'item_id'    => $itemId,
            'item_type'  => "order"
        ]);
    }

    public function updateRequest($request_id, array $updates): bool
    {

        $update_result = $this->databaseService->update(
            $this->requestTableName,
            $updates,
            [
                'id' => $request_id
            ]
        );

        if ( $update_result ) {
            try {
                $this->addRequestLogItem(
                    $request_id,
                    "Updated request. New data: " . json_encode($updates, JSON_THROW_ON_ERROR)
                );
            } catch (JsonException $e) {}

            return true;
        }

        return false;
    }

    public function getRequest(int $request_id): ?Request
    {
        $data = $this->databaseService->getById(
            $this->requestTableName,
            $request_id
        );

        if($data!==null) {
            return $this->requestFactory->fromArray(
                $data
            );
        }

        return null;
    }

    public function addRequestLogItem($request_id, $description): int
    {
        return $this->databaseService->insert(
            $this->logTableName,
            [
                'request_id'  => $request_id,
                'description' => $description,
                'timestamp'   => date( "Y-m-d H:i:s" ),
                'user_id'     => $this->currentUser->ID
            ]
        );

    }

    public function deleteRequest(int $request_id): bool
    {
        $deleteRequest  = $this->databaseService->delete( $this->requestTableName, [ 'id' => $request_id ] );
        $deleteRequestLogs = $this->databaseService->delete(  $this->logTableName, [ 'request_id' => $request_id ] );

        return $deleteRequest && $deleteRequestLogs;
    }

    // Helper functions

    private function validatedRequestObject( Request $request ): bool {
        // check if required keys are present and well-formed
//        if ( ! $this->requestWellFormed( $request ) ) {
//            return false;
//        }
        return true;
    }

    public function getRequestLogs(int $requestId): ?array
    {
        return $this->databaseService->query("bluem_requests_log", "SELECT *  FROM {TABLENAME} WHERE `request_id` = $requestId ORDER BY `timestamp` DESC" );
    }

    public function getRequestLinksByItemId(int $requestId): ?array
    {
        return $this->databaseService->query("bluem_requests_links", "SELECT *  FROM  {TABLENAME} WHERE `item_id` = {$requestId} and `item_type` = 'order'ORDER BY `timestamp` DESC" );
    }

    public function getRequestLinksByRequestId(int $requestId): ?array
    {
        return $this->databaseService->query("bluem_requests_links", "SELECT *  FROM  {TABLENAME} WHERE `request_id` = {$id} ORDER BY `timestamp` DESC" );
    }


    private function requestWellFormed(array $request_array): bool
    {
        $request = $this->requestFactory->fromArray($request_array);

        // @todo: check all available fields on their format
        return true;
    }
}
