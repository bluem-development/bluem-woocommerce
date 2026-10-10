<?php

namespace Bluem\Wordpress\Requests;

use Throwable;

/**
 * Shared reads from Bluem's request table. Callers supply user IDs explicitly;
 * WordPress adapters own current-user lookup and database selection.
 *
 * Field/sort names must come from trusted application code, as in the legacy
 * helpers. Values are passed through wpdb::prepare().
 */
final class BluemRequestRepository
{
    /** @param \wpdb $database WordPress database connection (or a test double). */
    public function __construct(private readonly object $database)
    {
    }

    public function findById(string $request_id)
    {
        // @todo change to only accept int for $request_id

        $res = $this->findByField(
            'id',
            $request_id
        );

        return $res[0] ?? false;
    }

    public function findByDebtorReference($debtor_reference)
    {
        $res = $this->findByField(
            'debtor_reference',
            $debtor_reference
        );

        return $res !== false && count($res) > 0 ? $res[0] : false;
    }

    public function findByTransactionId($transaction_id)
    {
        $res = $this->findByField(
            'transaction_id',
            $transaction_id
        );

        return $res !== false && count($res) > 0 ? $res[0] : false;
    }

    public function findByTransactionIdAndType($transaction_id, $type)
    {
        $res = $this->findBy(
            [
                'transaction_id' => $transaction_id,
                'type' => $type,
            ]
        );

        return $res !== false && count($res) > 0 ? $res[0] : false;
    }

    public function findByTransactionIdAndEntranceCode($transaction_id, $entrance_code)
    {
        $res = $this->findBy(
            [
                'transaction_id' => $transaction_id,
                'entrance_code' => $entrance_code,
            ]
        );

        return $res !== false && count($res) > 0 ? $res[0] : false;
    }

    public function findByField(
        $key,
        $value,
        $sort_key = null,
        $sort_dir = 'ASC',
        $limit = 0
    ) {
        return $this->findBy(
            [$key => $value],
            $sort_key,
            $sort_dir,
            $limit
        );
    }

    public function findBy(
        $keyvalues = [],
        $sort_key = null,
        $sort_dir = 'ASC',
        $limit = 0
    ) {
        $this->database->show_errors(); // Show or display errors

        // Start building the query
        $query = 'SELECT * FROM `' . $this->database->prefix . 'bluem_requests`';
        $where_clauses = [];
        $query_values = [];

        // Add conditions if key-value pairs are provided
        if (count($keyvalues) > 0) {
            foreach ($keyvalues as $key => $value) {
                if (!empty($key) && $value !== '') {
                    $where_clauses[] = "`{$key}` = %s";
                    $query_values[] = $value;
                }
            }

            if (!empty($where_clauses)) {
                $query .= ' WHERE ' . implode(' AND ', $where_clauses);
            }
        }

        // Add sorting if sort_key is provided
        if (!is_null($sort_key) && $sort_key !== '' && in_array(strtoupper($sort_dir), ['ASC', 'DESC'])) {
            $query .= " ORDER BY `{$sort_key}` " . strtoupper($sort_dir);
        }

        // Add limit if provided
        if (is_numeric($limit) && $limit > 0) {
            $query .= ' LIMIT %d';
            $query_values[] = $limit;
        }

        // Prepare the query with the provided values
        if (!empty($query_values)) {
            $query = $this->database->prepare($query, ...$query_values);
        }

        try {
            return $this->database->get_results($query);
        } catch (Throwable $th) {
            return false;
        }
    }

    public function findForUser($user_id)
    {
        $res = $this->findByField(
            'user_id',
            $user_id
        );

        return $res !== false && count($res) > 0 ? $res : [];
    }

    public function findForUserAndType($user_id, $type = '')
    {
        // @todo Throw an error when type is not given, or default to wildcard

        $res = $this->findBy(
            [
                'user_id' => $user_id,
                'type' => $type,
            ],
            'timestamp',
            'DESC'
        );

        return $res !== false && count($res) > 0 ? $res : [];
    }

    public function findMostRecent($user_id, $type = 'mandates')
    {
        // Validate the type against allowed values
        if (!in_array($type, ['mandates', 'payments', 'identity'])) {
            return false;
        }

        $this->database->show_errors(); // Enable error display

        try {
            $results = $this->database->get_results(
                $this->database->prepare(
                    'SELECT *
                    FROM `' . $this->database->prefix . 'bluem_requests`
                    WHERE `user_id` = %d
                        AND `type` = %s
                    ORDER BY `timestamp` DESC
                    LIMIT 1',
                    $user_id,
                    $type
                )
            );

            if (count($results) > 0) {
                return $results[0];
            }

            return false;
        } catch (Throwable $th) {
            return false;
        }
    }
}
