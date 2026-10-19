<?php

namespace Bluem\Wordpress\Requests;

/** Request audit persistence; adapters supply the current user and UTC time. */
final class BluemRequestLogRepository
{
    /** @param \wpdb $database WordPress database connection (or a test double). */
    public function __construct(private readonly object $database)
    {
    }

    public function append($requestId, $description, $timestamp, $userId)
    {
        return $this->database->insert(
            $this->database->prefix . 'bluem_requests_log',
            [
                'request_id' => $requestId,
                'description' => $description,
                'timestamp' => $timestamp,
                'user_id' => $userId,
            ]
        );
    }

    public function findForRequest($requestId)
    {
        return $this->database->get_results(
            $this->database->prepare(
                'SELECT * FROM `' . $this->database->prefix . 'bluem_requests_log` WHERE `request_id` = %d ORDER BY `timestamp` DESC',
                $requestId
            )
        );
    }
}
