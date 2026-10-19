<?php

namespace Bluem\Wordpress\Requests;

/** Request associations; the WordPress adapter owns the legacy schema guard. */
final class BluemRequestLinkRepository
{
    /** @param \wpdb $database WordPress database connection (or a test double). */
    public function __construct(private readonly object $database)
    {
    }

    public function create($requestId, $itemId, $itemType = 'order'): int
    {
        $result = $this->database->insert(
            $this->database->prefix . 'bluem_requests_links',
            [
                'request_id' => $requestId,
                'item_id' => $itemId,
                'item_type' => $itemType,
            ]
        );

        return $result ? $this->database->insert_id : -1;
    }

    public function findForOrder($orderId)
    {
        return $this->database->get_results(
            $this->database->prepare(
                'SELECT * FROM `' . $this->database->prefix . "bluem_requests_links` WHERE `item_id` = %d AND `item_type` = 'order' ORDER BY `timestamp` DESC",
                $orderId
            )
        );
    }

    public function findForRequest($requestId)
    {
        return $this->database->get_results(
            $this->database->prepare(
                'SELECT * FROM `' . $this->database->prefix . 'bluem_requests_links` WHERE `request_id` = %d ORDER BY `timestamp` DESC',
                $requestId
            )
        );
    }
}
